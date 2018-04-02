<?php

namespace Shippinno\Job\Test\Application\Messaging;

use PHPUnit\Framework\TestCase;
use Shippinno\Job\Application\Messaging\ListAbandonedJobMessagesService;
use Shippinno\Job\Domain\Model\AbandonedJobMessage;
use Shippinno\Job\Infrastructure\Application\Messaging\ArrayAbandonedJobMessageDataTransformer;
use Shippinno\Job\Test\Infrastructure\Domain\Model\InMemoryAbandonedJobMessageStore;

class ListAbandonedJobMessagesServiceTest extends TestCase
{
    public function testThatEmptyArrayIsReturnedIfNoMessages()
    {
        $abandonedJobMessageStore = new InMemoryAbandonedJobMessageStore;
        $abandonedJobMessageDataTransformer = new ArrayAbandonedJobMessageDataTransformer;
        $service = new ListAbandonedJobMessagesService($abandonedJobMessageStore, $abandonedJobMessageDataTransformer);
        $result = $service->execute();
        $this->assertSame([], $result);
    }

    public function testThatAllMessagesAreListed()
    {
        $abandonedJobMessage1 = new AbandonedJobMessage('queue1', 'message1', 'reason1');
        $abandonedJobMessage2 = new AbandonedJobMessage('queue2', 'message2', 'reason2');
        $abandonedJobMessageStore = new InMemoryAbandonedJobMessageStore;
        $abandonedJobMessageStore->add($abandonedJobMessage1);
        $abandonedJobMessageStore->add($abandonedJobMessage2);
        $abandonedJobMessageDataTransformer = new ArrayAbandonedJobMessageDataTransformer;
        $service = new ListAbandonedJobMessagesService($abandonedJobMessageStore, $abandonedJobMessageDataTransformer);
        $result = $service->execute();
        $this->assertCount(2, $result);
        $this->assertSame(1, $result[1]['id']);
        $this->assertSame($abandonedJobMessage1->queue(), $result[1]['queue']);
        $this->assertSame($abandonedJobMessage1->message(), $result[1]['message']);
        $this->assertSame($abandonedJobMessage1->reason(), $result[1]['reason']);
        $this->assertSame($abandonedJobMessage1->abandonedAt(), $result[1]['abandonedAt']);
        $this->assertSame(2, $result[2]['id']);
        $this->assertSame($abandonedJobMessage2->queue(), $result[2]['queue']);
        $this->assertSame($abandonedJobMessage2->message(), $result[2]['message']);
        $this->assertSame($abandonedJobMessage2->reason(), $result[2]['reason']);
        $this->assertSame($abandonedJobMessage2->abandonedAt(), $result[2]['abandonedAt']);
    }
}
