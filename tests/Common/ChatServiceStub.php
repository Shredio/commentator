<?php declare(strict_types = 1);

namespace Tests\Common;

use AIAccess\Chat\Chat;
use AIAccess\Chat\Response;

final class ChatServiceStub extends Chat
{

	public function __construct(
		private readonly ?string $text = null,
	)
	{
	}

	protected function generateResponse(): Response
	{
		return new ChatResponseStub($this->text);
	}

}
