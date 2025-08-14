<?php declare(strict_types = 1);

namespace Shredio\Commentator\Flow;

use DateTimeImmutable;
use LogicException;
use Shredio\Commentator\Enum\Gender;
use Shredio\Commentator\Input\CommentInput;
use Shredio\Commentator\Input\ThreadInput;
use Shredio\Commentator\Output\CommentOutput;
use Shredio\Commentator\Output\ThreadOutput;

final readonly class ThreadFlow
{

	public static function buildHierarchy(ThreadInput|ThreadOutput $input): string
	{
		$result = self::formatWithAuthorAndDate($input->content, $input->author->nickname, $input->author->gender, $input->createdAt);
		$result .= self::buildCommentsHierarchy($input->comments, '');
		return $result;
	}

	/**
	 * @param list<CommentInput|CommentOutput> $comments
	 */
	private static function buildCommentsHierarchy(array $comments, string $baseIndent): string
	{
		if (!$comments) {
			return '';
		}
		
		$result = '';
		$totalComments = count($comments);
		
		foreach ($comments as $index => $comment) {
			$isLast = $index === $totalComments - 1;
			$prefix = $baseIndent . ($isLast ? '└── ' : '├── ');
			$childIndent = $baseIndent . ($isLast ? '    ' : '│   ');

			$commentFormatted = self::formatWithAuthorAndDate($comment->content, $comment->author->nickname, $comment->author->gender, $comment->createdAt);
			$result .= "\n" . $prefix . self::formatMultilineWithIndent($commentFormatted, $childIndent);
			$result .= self::buildCommentsHierarchy($comment->comments, $childIndent);
		}
		
		return $result;
	}

	private static function formatWithAuthorAndDate(string $content, string $nickname, Gender $gender, DateTimeImmutable $createdAt): string
	{
		$formattedDate = $createdAt->format('Y-m-d');
		$genderSymbol = $gender === Gender::Male ? 'M' : 'F';
		$normalizedContent = self::normalize($content);

		$lines = explode("\n", $normalizedContent);
		$firstLine = array_shift($lines);
		$header = sprintf('@%s (%s) [%s]: %s', $nickname, $genderSymbol, $formattedDate, $firstLine);

		if ($lines) {
			$padding = str_repeat(' ', mb_strlen(sprintf('@%s (%s) [%s]: ', $nickname, $genderSymbol, $formattedDate)));
			foreach ($lines as $line) {
				$header .= "\n" . $padding . $line;
			}
		}

		return $header;
	}

	private static function normalize(string $content): string
	{
		$content = strip_tags(strtr($content, ['</p>' => "</p>\n"]));
		$content = preg_replace('/ +/', ' ', $content);
		if ($content === null) {
			throw new LogicException('Failed to normalize content.');
		}

		$content = preg_replace('/ *\n+ */', "\n", $content);
		if ($content === null) {
			throw new LogicException('Failed to normalize content.');
		}

		return trim(html_entity_decode($content));
	}

	/**
	 * @param list<ThreadFlowItem> $childPosts
	 * @param list<ThreadFlowItem> $sameLevelPosts
	 */
	public function __construct(
		private ThreadFlowItem $mainPost,
		private array $childPosts = [],
		private array $sameLevelPosts = [],
	)
	{
	}

	public function withNestedPost(ThreadFlowItem $post): self
	{
		$newChildPosts = $this->childPosts;
		$newChildPosts[] = $post;

		return new self(
			$this->mainPost,
			$newChildPosts,
		);
	}

	public function withNextPost(ThreadFlowItem $post): self
	{
		$newSameLevelPosts = $this->sameLevelPosts;
		$newSameLevelPosts[] = $post;

		return new self(
			$this->mainPost,
			$this->childPosts,
			$newSameLevelPosts,
		);
	}

	public function toString(): string
	{
		$result = self::formatWithAuthorAndDate(
			$this->mainPost->content,
			$this->mainPost->nick,
			$this->mainPost->gender,
			$this->mainPost->date
		);
		
		$indent = '';
		
		// Add nested child posts
		foreach ($this->childPosts as $childPost) {
			$childFormatted = self::formatWithAuthorAndDate(
				$childPost->content,
				$childPost->nick,
				$childPost->gender,
				$childPost->date
			);
			$result .= "\n" . $indent . '└── ' . self::formatMultilineWithIndent($childFormatted, $indent . '    ');
			$indent .= '    ';
		}
		
		// Add same level posts at current depth
		foreach ($this->sameLevelPosts as $sameLevelPost) {
			$postFormatted = self::formatWithAuthorAndDate(
				$sameLevelPost->content,
				$sameLevelPost->nick,
				$sameLevelPost->gender,
				$sameLevelPost->date
			);
			$prefix = $indent . '├── ';
			$result .= "\n" . $prefix . self::formatMultilineWithIndent($postFormatted, $indent . '│   ');
		}

		$result .= "\n" . $indent . '└── **Current content of post you must translate**';

		return $result . "\n";
	}

	private static function formatMultilineWithIndent(string $content, string $indent): string
	{
		$lines = explode("\n", $content);
		$firstLine = array_shift($lines);
		$result = $firstLine;
		
		if ($lines) {
			foreach ($lines as $line) {
				$result .= "\n" . $indent . $line;
			}
		}
		
		return $result;
	}

}
