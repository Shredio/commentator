<?php declare(strict_types = 1);

namespace Tests\Common;

use AIAccess\Chat\Chat;
use AIAccess\Chat\Service;

final readonly class ChatServiceFactoryStub implements Service
{

	public function __construct(
		private ?string $text = null,
	)
	{
	}

	public function createChat(string $model): Chat
	{
		return new ChatServiceStub($this->text);
	}

}
