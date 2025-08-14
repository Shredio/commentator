<?php declare(strict_types = 1);

namespace Tests;

use AIAccess\Chat\Service;
use Tests\Common\ChatServiceFactoryStub;
use Tests\Common\MessageCapturingChatFactoryStub;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{

	protected function createAiService(?string $textToReturn = null): Service
	{
		return new ChatServiceFactoryStub($textToReturn);
	}

	protected function createMessageCapturingAiService(): Service
	{
		return new MessageCapturingChatFactoryStub();
	}

}
