<?php declare(strict_types = 1);

namespace Tests\Common;

use AIAccess\Chat\Chat;
use AIAccess\Chat\Response;

final class MessageCapturingChatStub extends Chat
{

	private ?string $lastMessage = null;

	public function sendMessage(?string $message = null): Response
	{
		$this->lastMessage = $message;

		return parent::sendMessage($message);
	}

	protected function generateResponse(): Response
	{
		return new ChatResponseStub($this->lastMessage);
	}

}
