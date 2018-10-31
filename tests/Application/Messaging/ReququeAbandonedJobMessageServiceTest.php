<?php

namespace Shippinno\Job\Test\Application\Messaging;

use DateTimeImmutable;
use Enqueue\Null\NullContext;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Mockery;
use PHPUnit\Framework\TestCase;
use Shippinno\Job\Application\Messaging\RequeueAbandonedJobMessageService;
use Shippinno\Job\Domain\Model\AbandonedJobMessage;
use Shippinno\Job\Domain\Model\AbandonedJobMessageFailedToRequeueException;
use Shippinno\Job\Domain\Model\StoredJob;
use Shippinno\Job\Domain\Model\StoredJobSerializer;
use Shippinno\Job\Test\Domain\Model\FakeStoredJob;
use Shippinno\Job\Test\Domain\Model\SimpleStoredJobSerializer;
use Shippinno\Job\Test\Infrastructure\Domain\Model\InMemoryAbandonedJobMessageStore;

class ReququeAbandonedJobMessageServiceTest extends TestCase
{
    /**
     * @expectedException \Shippinno\Job\Domain\Model\AbandonedJobMessageNotFoundException
     */
    public function testThatExceptionIsThrownIfMessageNotFound()
    {
        $context = new NullContext;
        $abandonedJobMessageStore = new InMemoryAbandonedJobMessageStore;
        $storedJobSerializer = Mockery::mock(StoredJobSerializer::class);
        $service = new RequeueAbandonedJobMessageService(
            $context,
            $abandonedJobMessageStore,
            $storedJobSerializer
        );
        $service->execute(1);
    }

    public function testThatExceptionIsThrownIfFailsToRequeue()
    {
        $abandonedJobMessage = new AbandonedJobMessage('QUEUE_NAME', 'MESSAGE', 'reason');
        $abandonedJobMessageStore = new InMemoryAbandonedJobMessageStore;
        $abandonedJobMessageStore->add($abandonedJobMessage);
        $storedJobSerializer = Mockery::mock(StoredJobSerializer::class);
        $storedJobSerializer->shouldReceive([
            'deserialize' => new FakeStoredJob('name', 'body', new DateTimeImmutable(), 1),
        ]);
        $producer = Mockery::mock(Producer::class);
        $producer
            ->shouldReceive('send')
            ->once()
            ->andThrow(new InvalidMessageException);
        $context = Mockery::mock(NullContext::class)->makePartial();
        $context->shouldReceive('createProducer')->once()->andReturn($producer);
        $service = new RequeueAbandonedJobMessageService($context, $abandonedJobMessageStore, $storedJobSerializer);
        try {
            $service->execute(1);
        } catch (AbandonedJobMessageFailedToRequeueException $e) {
            $this->assertTrue(true);
        }
        $this->assertCount(1, $abandonedJobMessageStore->all());
    }

    public function testThatMessageIsRequeuedAndDeleted()
    {
        $abandonedJobMessage = new AbandonedJobMessage('QUEUE_NAME', 'MESSAGE', 'reason');
        $abandonedJobMessageStore = new InMemoryAbandonedJobMessageStore;
        $abandonedJobMessageStore->add($abandonedJobMessage);
        $storedJobSerializer = Mockery::mock(StoredJobSerializer::class);
        $storedJobSerializer->shouldReceive([
            'deserialize' => new FakeStoredJob('name', 'body', new DateTimeImmutable(), 1),
        ]);
        $producer = Mockery::mock(Producer::class);
        $producer
            ->shouldReceive('send')
            ->withArgs([
                Mockery::on(function (Queue $queue) {
                    return 'QUEUE_NAME' === $queue->getQueueName();
                }),
                Mockery::on(function (Message $message) {
                    return 'MESSAGE' === $message->getBody();
                }),
            ])
            ->once();
        $context = Mockery::mock(NullContext::class)->makePartial();
        $context->shouldReceive('createProducer')->once()->andReturn($producer);
        $service = new RequeueAbandonedJobMessageService($context, $abandonedJobMessageStore, $storedJobSerializer);
        $service->execute(1);
        $this->assertCount(0, $abandonedJobMessageStore->all());
    }
}
