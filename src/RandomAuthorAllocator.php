<?php declare(strict_types = 1);

namespace Shredio\Commentator;

use InvalidArgumentException;
use Shredio\Commentator\Exception\UnavailableAuthorIdException;
use Shredio\Commentator\ValueObject\Author;

final class RandomAuthorAllocator implements AuthorAllocator
{
    /** @var array<string, Author> */
    private array $allocatedAuthors;

    /** @var array<int, Author> */
    private array $availableAuthors;

    /**
     * @param array<int, Author> $authors
     */
    public function __construct(
        private readonly array $authors,
    ) {
        if (count($this->authors) < 5) {
            throw new InvalidArgumentException('At least 5 authors must be provided for allocation.');
        }

        $this->allocatedAuthors = [];
        $this->availableAuthors = $this->authors;
    }

    public function allocate(Author $author): Author
    {
        return $this->allocatedAuthors[$author->id] ??= $this->allocateAuthorId();
    }

    public function reset(): void
    {
        $this->allocatedAuthors = [];
        $this->availableAuthors = $this->authors;
    }

    /**
     * @throws UnavailableAuthorIdException
     */
    private function allocateAuthorId(): Author
    {
        if ($this->availableAuthors === []) {
            throw new UnavailableAuthorIdException();
        }

        $key = array_rand($this->availableAuthors);
        $allocated = $this->availableAuthors[$key];
        unset($this->availableAuthors[$key]);

        return $allocated;
    }
}
