<?php declare(strict_types = 1);

namespace Shredio\Commentator\ValueObject;

use Shredio\Commentator\Enum\Gender;

final readonly class Author
{

	public function __construct(
		public string $id,
		public string $nickname,
		public Gender $gender = Gender::Male,
	)
	{
	}

}
