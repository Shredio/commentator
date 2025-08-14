<?php declare(strict_types = 1);

namespace Shredio\Commentator\Output;

use DateTimeImmutable;
use Shredio\Commentator\ValueObject\Author;

abstract readonly class PostOutput
{

	/**
	 * @param list<CommentOutput> $comments List of comments associated with the post.
	 */
	public function __construct(
		public string $content,
		public Author $author,
		public DateTimeImmutable $createdAt,
		public array $comments = [],
	)
	{
	}

}
