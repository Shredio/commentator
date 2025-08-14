<?php declare(strict_types = 1);

namespace Shredio\Commentator\Event;

use Shredio\Commentator\Input\PostInput;
use Shredio\Commentator\Input\ThreadInput;

final readonly class ItemProcessingEvent
{

	public function __construct(
		public ThreadInput $threadInput,
		public PostInput $postInput,
	)
	{
	}

}
