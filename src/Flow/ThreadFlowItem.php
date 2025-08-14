<?php declare(strict_types = 1);

namespace Shredio\Commentator\Flow;

use DateTimeImmutable;
use Shredio\Commentator\Enum\Gender;

final readonly class ThreadFlowItem
{

	public function __construct(
		public string $content,
		public string $nick,
		public Gender $gender,
		public DateTimeImmutable $date,
	)
	{
	}

}