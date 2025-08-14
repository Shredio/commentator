<?php declare(strict_types = 1);

namespace Tests\Common;

use DateTimeImmutable;
use Shredio\Commentator\DateTimeGenerator;
use Shredio\Commentator\DateTimeGeneratorFactory;
use Shredio\Commentator\Input\ThreadInput;

final readonly class FixedDateTimeGeneratorFactory implements DateTimeGeneratorFactory
{

	public function __construct(
		private DateTimeImmutable $fixedDateTime,
	)
	{
	}

	public function create(ThreadInput $input): DateTimeGenerator
	{
		return new FixedDateTimeGenerator($this->fixedDateTime);
	}

}
