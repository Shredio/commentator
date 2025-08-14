<?php declare(strict_types = 1);

namespace Shredio\Commentator;

use AIAccess\Chat\Response;
use AIAccess\Chat\Service;
use AIAccess\Chat\Usage;
use AIAccess\ServiceException;
use DateTimeImmutable;
use LogicException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shredio\Commentator\Enum\Gender;
use Shredio\Commentator\Event\ItemProcessingEvent;
use Shredio\Commentator\Exception\UnavailableAuthorIdException;
use Shredio\Commentator\Flow\ThreadFlow;
use Shredio\Commentator\Flow\ThreadFlowItem;
use Shredio\Commentator\Input\PostInput;
use Shredio\Commentator\Input\ThreadInput;
use Shredio\Commentator\Output\CommentOutput;
use Shredio\Commentator\Output\ThreadOutput;
use Shredio\Commentator\ValueObject\Author;

final readonly class Commentator
{
	private const int MaxRetryAttempts = 3;
	private const string TargetLocaleTemplate = '**Target locale:** "%s"';
	private const string GenderTemplate = '**The resulting content must be in following gender:** "%s"';
	private const string HierarchyHeader = '**Hierarchy:**';
	private const string ContentHeader = '**Content to process:**';
	private const string TripleQuotes = '"""';

	/**
	 * @param list<CommentatorContentProcessor> $contentProcessors
	 */
	public function __construct(
		private Service $chatFactory,
		private string $model,
		private string $instruction,
		private string $locale,
		private AuthorAllocator $authorAllocator,
		private DateTimeGenerator $dateTimeGenerator,
		private array $contentProcessors = [],
		private ?EventDispatcherInterface $eventDispatcher = null,
	)
	{
	}

	public function comment(ThreadInput $thread, ?InfoToCapture $info = null): ?ThreadOutput
	{
		$this->authorAllocator->reset();

		$output = $this->processThread($thread, $info);

		$this->authorAllocator->reset();

		return $output;
	}

	private function processThread(ThreadInput $input, ?InfoToCapture $info): ?ThreadOutput
	{
		$content = $this->processContent($input, $input, info: $info);
		if ($content === null) {
			return null;
		}

		$targetDate = $this->dateTimeGenerator->generateTargetDate();
		$author = $this->getPostAuthor($input->author);
		$threadFlowItem = new ThreadFlowItem($content, $author->nickname, $author->gender, $targetDate);

		return new ThreadOutput(
			$content,
			$author,
			$targetDate,
			$this->processComments($input, new ThreadFlow($threadFlowItem), $input, $content, $input->createdAt, $targetDate, $info),
		);
	}

	/**
	 * @return list<CommentOutput>
	 */
	private function processComments(
		ThreadInput $thread,
		ThreadFlow $threadFlow,
		PostInput $parent,
		string $threadContent,
		DateTimeImmutable $referenceDate,
		DateTimeImmutable $targetDate,
		?InfoToCapture $info,
	): array
	{
		$comments = [];
		foreach ($parent->comments as $comment) {
			$processedComment = $this->processSingleComment(
				$thread,
				$comment,
				$threadFlow,
				$threadContent,
				$referenceDate,
				$targetDate,
				$info,
			);

			if ($processedComment !== null) {
				$comments[] = $processedComment['output'];
				$threadFlow = $processedComment['threadFlow'];
			}
		}

		return $comments;
	}

	/**
	 * @throws UnavailableAuthorIdException
	 * @throws ServiceException
	 */
	private function processContent(
		ThreadInput $thread,
		PostInput $input,
		?ThreadFlow $threadFlow = null,
		?InfoToCapture $info = null,
	): ?string
	{
		$this->eventDispatcher?->dispatch(new ItemProcessingEvent($thread, $input));

		$content = $this->applyPreprocessors($thread, $input);
		if ($content === null) {
			return null;
		}

		return $this->attemptContentProcessing($thread, $input, $content, $threadFlow, $info);
	}

	private function applyPreprocessors(ThreadInput $thread, PostInput $input): ?string
	{
		$content = $input->getNormalizedContent();
		if ($content === '') {
			return null;
		}

		foreach ($this->contentProcessors as $processor) {
			$content = $processor->preprocess($this, $thread, $content, $input);
			if ($content === null) {
				return null;
			}
		}

		return $content;
	}

	/**
	 * @throws UnavailableAuthorIdException
	 * @throws ServiceException
	 */
	private function attemptContentProcessing(
		ThreadInput $thread,
		PostInput $input,
		string $content,
		?ThreadFlow $threadFlow,
		?InfoToCapture $info,
	): ?string
	{
		$lastException = null;

		for ($attemptNumber = 0; $attemptNumber < self::MaxRetryAttempts; $attemptNumber++) {
			try {
				$result = $this->processWithAI($thread, $input, $content, $threadFlow, $info);
				if ($result !== null) {
					return $result;
				}
				// If result is null due to postprocessor, we don't retry
				return null;
			} catch (ServiceException $exception) {
				$lastException = $exception;
			}
		}

		throw $lastException;
	}

	/**
	 * @throws UnavailableAuthorIdException
	 * @throws ServiceException
	 */
	private function processWithAI(
		ThreadInput $thread,
		PostInput $input,
		string $content,
		?ThreadFlow $threadFlow,
		?InfoToCapture $info,
	): ?string
	{
		$chat = $this->chatFactory->createChat($this->model);
		$chat->setSystemInstruction($this->getSystemInstruction());

		$postAuthor = $this->getPostAuthor($input->author);
		$response = $chat->sendMessage($this->createMessage(
			$content,
			$postAuthor->gender === Gender::Male ? 'M' : 'F',
			$threadFlow,
		));

		$this->addUsage($response, $info);

		return $this->validateAndProcessResponse($thread, $input, $response, $postAuthor);
	}

	private function validateAndProcessResponse(
		ThreadInput $thread,
		PostInput $input,
		Response $response,
		Author $postAuthor,
	): ?string
	{
		$text = $response->getText();
		if ($text === null || $text === '') {
			throw new LogicException('Empty response from AI model');
		}

		foreach ($this->contentProcessors as $processor) {
			$text = $processor->postprocess($this, $thread, $text, $input, $postAuthor);
			if ($text === null) {
				return null;
			}
		}

		return $text;
	}

	private function getSystemInstruction(): string
	{
		return strtr($this->instruction, [
			'{locale}' => $this->locale,
		]);
	}

	/**
	 * @throws UnavailableAuthorIdException
	 */
	public function getPostAuthor(Author $author): Author
	{
		return $this->authorAllocator->allocate($author);
	}

	private function getCreatedAt(
		DateTimeImmutable $createdAt,
		DateTimeImmutable $referenceDate,
		DateTimeImmutable $targetDate,
	): DateTimeImmutable
	{
		$interval = $referenceDate->diff($createdAt);

		return $targetDate->add($interval);
	}

	private function addUsage(Response $response, ?InfoToCapture $info): void
	{
		if ($info === null) {
			return;
		}

		$usage = $response->getUsage();
		if ($usage === null) {
			return;
		}

		$this->addInputTokens($usage, $info);
		$this->addOutputTokens($usage, $info);
		$this->addReasoningTokens($usage, $info);
		$this->addCachedTokens($usage, $info);
	}

	private function addInputTokens(Usage $usage, InfoToCapture $info): void
	{
		if ($usage->inputTokens !== null) {
			$info->inputTokens += $usage->inputTokens;
		}
	}

	private function addOutputTokens(Usage $usage, InfoToCapture $info): void
	{
		if ($usage->outputTokens !== null) {
			$info->outputTokens += $usage->outputTokens;
		}
	}

	private function addReasoningTokens(Usage $usage, InfoToCapture $info): void
	{
		if ($usage->reasoningTokens !== null) {
			$info->reasoningTokens += $usage->reasoningTokens;
		} else {
			$reasoningTokens = $this->getValueFromArray($usage->raw, 'output_tokens_details', 'reasoning_tokens');
			$info->reasoningTokens += is_int($reasoningTokens) ? $reasoningTokens : 0;
		}
	}

	private function addCachedTokens(Usage $usage, InfoToCapture $info): void
	{
		$cachedTokens = $this->getValueFromArray($usage->raw, 'input_tokens_details', 'cached_tokens');
		if (is_int($cachedTokens)) {
			$info->cachedTokens += $cachedTokens;
		}
	}

	/**
	 * @param array<array-key, mixed> $values
	 */
	private function getValueFromArray(array $values, string ...$keys): mixed
	{
		$value = $values;
		foreach ($keys as $key) {
			if (!is_array($value)) {
				return null;
			}
			if (!isset($value[$key])) {
				return null;
			}
			$value = $value[$key];
		}

		return $value;
	}

	private function createMessage(string $content, string $gender, ?ThreadFlow $threadFlow): string
	{
		$message = $this->buildLocaleAndGenderInfo($gender) . "\n";

		if ($threadFlow !== null) {
			$message .= $this->buildHierarchySection($threadFlow);
		}

		$message .= $this->buildContentSection($content);

		return $message;
	}

	private function buildLocaleAndGenderInfo(string $gender): string
	{
		return sprintf(self::TargetLocaleTemplate, $this->locale) . "\n" .
			sprintf(self::GenderTemplate, $gender);
	}

	private function buildHierarchySection(?ThreadFlow $threadFlow): ?string
	{
		if ($threadFlow === null) {
			return null;
		}

		return self::HierarchyHeader . "\n" .
			self::TripleQuotes . "\n" .
			$threadFlow->toString() .
			self::TripleQuotes . "\n";
	}

	private function buildContentSection(string $content): string
	{
		return self::ContentHeader . "\n" .
			self::TripleQuotes . "\n" .
			$content .
			self::TripleQuotes . "\n";
	}

	/**
	 * @return array{output: CommentOutput, threadFlow: ThreadFlow}|null
	 */
	private function processSingleComment(
		ThreadInput $thread,
		PostInput $comment,
		ThreadFlow $threadFlow,
		string $threadContent,
		DateTimeImmutable $referenceDate,
		DateTimeImmutable $targetDate,
		?InfoToCapture $info,
	): ?array
	{
		try {
			$content = $this->processContent($thread, $comment, $threadFlow, $info);
			if ($content === null) {
				return null;
			}

			$author = $this->getPostAuthor($comment->author);
			$createdAt = $this->getCreatedAt($comment->createdAt, $referenceDate, $targetDate);
			$threadFlowItem = $this->createThreadFlowItem($content, $author, $createdAt);

			$commentOutput = new CommentOutput(
				$content,
				$author,
				$createdAt,
				$this->processComments($thread, $threadFlow->withNestedPost($threadFlowItem), $comment, $threadContent, $referenceDate, $targetDate, $info),
			);

			return [
				'output' => $commentOutput,
				'threadFlow' => $threadFlow->withNextPost($threadFlowItem),
			];
		} catch (UnavailableAuthorIdException) {
			// If we run out of author IDs, we skip this comment.
			return null;
		}
	}

	private function createThreadFlowItem(
		string $content,
		Author $author,
		DateTimeImmutable $createdAt,
	): ThreadFlowItem
	{
		return new ThreadFlowItem(
			$content,
			$author->nickname,
			$author->gender,
			$createdAt,
		);
	}

}
