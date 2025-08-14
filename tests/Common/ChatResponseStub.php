<?php declare(strict_types = 1);

namespace Tests\Common;

use AIAccess\Chat\FinishReason;
use AIAccess\Chat\Response;
use AIAccess\Chat\Usage;

final readonly class ChatResponseStub implements Response
{

	public function __construct(
		private ?string $text = null,
	)
	{
	}

	public function getText(): ?string
	{
		return $this->text;
	}

	public function getFinishReason(): FinishReason
	{
		return FinishReason::Complete;
	}

	public function getUsage(): ?Usage
	{
		return null;
	}

	public function getRawResponse(): mixed
	{
		return [];
	}

	public function getRawFinishReason(): mixed
	{
		return null;
	}

}
