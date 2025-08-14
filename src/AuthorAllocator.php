<?php declare(strict_types = 1);

namespace Shredio\Commentator;

use Shredio\Commentator\Exception\UnavailableAuthorIdException;
use Shredio\Commentator\ValueObject\Author;

interface AuthorAllocator
{
    /**
     * @throws UnavailableAuthorIdException
     */
    public function allocate(Author $author): Author;

    public function reset(): void;
}