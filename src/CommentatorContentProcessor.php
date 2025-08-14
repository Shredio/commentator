<?php declare(strict_types = 1);

namespace Shredio\Commentator;

use Shredio\Commentator\Input\PostInput;
use Shredio\Commentator\Input\ThreadInput;
use Shredio\Commentator\ValueObject\Author;

interface CommentatorContentProcessor
{

	public function preprocess(
		Commentator $commentator,
		ThreadInput $thread,
		string $content,
		PostInput $input,
	): ?string;

	public function postprocess(
		Commentator $commentator,
		ThreadInput $thread,
		string $content,
		PostInput $input,
		Author $author,
	): ?string;

}
