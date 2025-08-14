<?php declare(strict_types = 1);

namespace Tests\Common;

use InvalidArgumentException;
use Shredio\Commentator\AuthorAllocator;
use Shredio\Commentator\Exception\UnavailableAuthorIdException;
use Shredio\Commentator\ValueObject\Author;

final class SequentialAuthorAllocator implements AuthorAllocator
{
    /** @var array<string, Author> */
    private array $allocatedAuthors = [];

    /** @var list<Author> */
    private array $availableAuthors = [];

    private int $currentIndex = 0;

    /**
     * @param list<Author> $authors
     */
    public function __construct(
        private readonly array $authors,
    ) {
        if (count($this->authors) < 5) {
            throw new InvalidArgumentException('At least 5 authors must be provided for allocation.');
        }

        $this->reset();
    }

    public function allocate(Author $author): Author
    {
        return $this->allocatedAuthors[$author->id] ??= $this->allocateAuthorId();
    }

    public function reset(): void
    {
        $this->allocatedAuthors = [];
        $this->availableAuthors = $this->authors;
        $this->currentIndex = 0;
    }

    /**
     * @throws UnavailableAuthorIdException
     */
    private function allocateAuthorId(): Author
    {
        if ($this->currentIndex >= count($this->availableAuthors)) {
            throw new UnavailableAuthorIdException();
        }

        $allocated = $this->availableAuthors[$this->currentIndex];
        $this->currentIndex++;

        return $allocated;
    }
}