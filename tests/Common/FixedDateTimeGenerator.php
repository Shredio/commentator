<?php declare(strict_types = 1);

namespace Tests\Common;

use DateTimeImmutable;
use Shredio\Commentator\DateTimeGenerator;

final readonly class FixedDateTimeGenerator implements DateTimeGenerator
{
    public function __construct(
        private DateTimeImmutable $fixedDateTime,
    ) {
    }

    public function generateTargetDate(): DateTimeImmutable
    {
        return $this->fixedDateTime;
    }
}