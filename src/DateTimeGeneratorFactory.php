<?php declare(strict_types = 1);

namespace Shredio\Commentator;

use Shredio\Commentator\Input\ThreadInput;

interface DateTimeGeneratorFactory
{

	public function create(ThreadInput $input): DateTimeGenerator;

}
