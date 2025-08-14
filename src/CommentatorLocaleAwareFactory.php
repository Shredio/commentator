<?php declare(strict_types = 1);

namespace Shredio\Commentator;

use AIAccess\Chat\Service;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Shredio\Commentator\Enum\Gender;
use Shredio\Commentator\ValueObject\Author;

final readonly class CommentatorLocaleAwareFactory implements CommentatorFactory
{

	/**
	 * @param array<string, array<int, Author>> $authors
	 * @param list<CommentatorContentProcessor> $processors
	 */
	public function __construct(
		private Service $chatService,
		private string $model,
		private string $instructionFile,
		private array $authors,
		private array $processors = [],
		private ?EventDispatcherInterface $eventDispatcher = null,
	)
	{
	}

	/**
	 * @param array<string, list<array{ id: string|int, nickname: string }>> $authors
	 * @param list<CommentatorContentProcessor> $processors
	 */
	public static function fromArray(
		Service $chatService,
		string $model,
		string $instructionFile,
		array $authors,
		array $processors = [],
		?EventDispatcherInterface $eventDispatcher = null,
	): self
	{
		return new self(
			$chatService,
			$model,
			$instructionFile,
			self::processAuthors($authors),
			$processors,
			$eventDispatcher,
		);
	}

	/**
	 * @param array<string, list<array{ id: string|int, nickname: string, gender?: 'male'|'female' }>> $authors
	 * @return array<string, list<Author>>
	 */
	public static function processAuthors(array $authors): array
	{
		return array_map(fn (array $authorList): array => array_map(
			fn (array $author): Author => new Author(
				(string) $author['id'],
				$author['nickname'], 
				strtolower(($author['gender'] ?? '')) === 'female' ? Gender::Female : Gender::Male,
			),
			$authorList,
		), $authors);
	}

	public function create(string $locale): Commentator
	{
		$contents = file_get_contents($this->instructionFile);
		if ($contents === false) {
			throw new RuntimeException("Could not read instruction file: $this->instructionFile");
		}

		$authors = $this->authors[$locale] ?? null;
		if ($authors === null) {
			throw new InvalidArgumentException("No authors found for locale: $locale");
		}

		return new Commentator(
			$this->chatService,
			$this->model,
			$contents,
			$locale,
			new RandomAuthorAllocator($authors),
			new RandomDateTimeGenerator(),
			$this->processors,
			$this->eventDispatcher,
		);
	}

	/**
	 * @return list<string>
	 */
	public function getAvailableLocales(): array
	{
		return array_keys($this->authors);
	}

}
