<?php

namespace Shippinno\Job\Test\Application\Messaging;

use PHPUnit\Framework\TestCase;
use Shippinno\Job\Application\Messaging\DeleteAbandonedJobMessageService;
use Shippinno\Job\Domain\Model\AbandonedJobMessage;
use Shippinno\Job\Domain\Model\AbandonedJobMessageNotFoundException;
use Shippinno\Job\Test\Infrastructure\Domain\Model\InMemoryAbandonedJobMessageStore;

class DeleteAbandonedJobMessagesServiceTest extends TestCase
{
    /**
     * @expectException \Shippinno\Job\Domain\Model\AbandonedJobMessageNotFoundException
     */
    public function testThatExceptionIsThrownIfMessageNotFound()
    {
        $this->expectException(AbandonedJobMessageNotFoundException::class);
        $service = new DeleteAbandonedJobMessageService(new InMemoryAbandonedJobMessageStore);
        $service->execute(1);
    }

    public function testThatMessageIsDeleted()
    {
        $abandonedJobMessage = new AbandonedJobMessage('queue', 'message', 'reason');
        $abandonedJobMessageStore = new InMemoryAbandonedJobMessageStore;
        $abandonedJobMessageStore->add($abandonedJobMessage);
        $service = new DeleteAbandonedJobMessageService($abandonedJobMessageStore);
        $service->execute(1);
        $this->assertCount(0, $abandonedJobMessageStore->all());
    }
}