<?php declare(strict_types = 1);

namespace Tests\Common;

use AIAccess\Chat\Chat;
use AIAccess\Chat\Service;
use Tests\Common\MessageCapturingChatStub;

final class MessageCapturingChatFactoryStub implements Service
{

	public function createChat(string $model): Chat
	{
		return new MessageCapturingChatStub();
	}

}
