<?php
/**
 * Created by PhpStorm.
 * User: tanigami
 * Date: 3/30/18
 * Time: 15:09
 */

namespace Shippinno\Job\Test\Application\Messaging;


use Shippinno\Job\Application\Messaging\DeleteAbandonedJobMessageService;
use Shippinno\Job\Domain\Model\AbandonedJobMessage;
use Shippinno\Job\Test\Infrastructure\Domain\Model\InMemoryAbandonedJobMessageStore;

class DeleteAbandonedJobMessagesServiceTest
{
    /**
     * @expectedException \Shippinno\Job\Domain\Model\AbandonedJobMessageNotFoundException
     */
    public function testThatExceptionIsThrownIfMessageNotFound()
    {
        $service = new DeleteAbandonedJobMessageService($abandonedJobMessageStore);
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