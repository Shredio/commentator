<?php declare(strict_types = 1);

namespace Shredio\Commentator;

use DateTimeImmutable;

interface DateTimeGenerator
{
	public function getTargetDate(): DateTimeImmutable;

	public function getDate(DateTimeImmutable $referenceDate): DateTimeImmutable;

}
