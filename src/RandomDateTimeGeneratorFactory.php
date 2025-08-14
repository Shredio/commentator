<?php declare(strict_types = 1);

namespace Shredio\Commentator;

use Shredio\Commentator\Input\ThreadInput;

final readonly class RandomDateTimeGeneratorFactory implements DateTimeGeneratorFactory
{

	public function create(ThreadInput $input): DateTimeGenerator
	{
		[$min, $max] = $input->getMinimumAndMaximumDates();

		return new RandomDateTimeGenerator($min, $max);
	}

}
