<?php declare(strict_types = 1);

namespace Shredio\Commentator\Input;

use DateTimeImmutable;
use Shredio\Commentator\ValueObject\Author;

abstract readonly class PostInput
{

	/**
	 * @param list<CommentInput> $comments List of comments associated with the post.
	 */
	public function __construct(
		public string $content,
		public Author $author,
		public DateTimeImmutable $createdAt,
		public array $comments = [],
	)
	{
	}

	public function getNormalizedContent(): string
	{
		return trim($this->content);
	}

	/**
	 * @return array<string, Author>
	 */
	public function getAuthorIndexByNicknames(): array
	{
		$authors = [$this->author->nickname => $this->author];
		
		foreach ($this->comments as $comment) {
			$authors = array_merge($authors, $comment->getAuthorIndexByNicknames());
		}
		
		return $authors;
	}

	public function getItemCount(): int
	{
		$count = 1;
		foreach ($this->comments as $comment) {
			$count += $comment->getItemCount();
		}

		return $count;
	}

	/**
	 * @return array{DateTimeImmutable, DateTimeImmutable} minimum, maximum
	 */
	public function getMinimumAndMaximumDates(): array
	{
		$min = $this->createdAt;
		$max = $this->createdAt;

		foreach ($this->comments as $comment) {
			[$nestedMin, $nestedMax] = $comment->getMinimumAndMaximumDates();
			if ($nestedMin < $min) {
				$min = $nestedMin;
			}
			if ($nestedMax > $max) {
				$max = $nestedMax;
			}
		}

		return [$min, $max];
	}

}
