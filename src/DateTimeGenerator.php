<?php declare(strict_types = 1);

namespace Shredio\Commentator;

use DateTimeImmutable;

interface DateTimeGenerator
{
    public function generateTargetDate(): DateTimeImmutable;
}