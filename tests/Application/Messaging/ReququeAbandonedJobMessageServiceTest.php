<?php

namespace Shippinno\Job\Test\Application\Messaging;

use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrQueue;
use Mockery;
use PHPUnit\Framework\TestCase;
use Shippinno\Job\Application\Messaging\RequeueAbandonedJobMessageService;
use Shippinno\Job\Domain\Model\AbandonedJobMessage;
use Shippinno\Job\Domain\Model\AbandonedJobMessageFailedToRequeueException;
use Shippinno\Job\Domain\Model\AbandonedJobMessageNotFoundException;
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
        $service = new RequeueAbandonedJobMessageService($context, $abandonedJobMessageStore);
        $service->execute(1);
    }

    public function testThatExceptionIsThrownIfFailsToRequeue()
    {
        $abandonedJobMessage = new AbandonedJobMessage('QUEUE_NAME', 'MESSAGE', 'reason');
        $abandonedJobMessageStore = new InMemoryAbandonedJobMessageStore;
        $abandonedJobMessageStore->add($abandonedJobMessage);
        $producer = Mockery::mock(PsrProducer::class);
        $producer
            ->shouldReceive('send')
            ->once()
            ->andThrow(new InvalidMessageException);
        $context = Mockery::mock(NullContext::class)->makePartial();
        $context->shouldReceive('createProducer')->once()->andReturn($producer);
        $service = new RequeueAbandonedJobMessageService($context, $abandonedJobMessageStore);
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
        $producer = Mockery::mock(PsrProducer::class);
        $producer
            ->shouldReceive('send')
            ->withArgs([
                Mockery::on(function (PsrQueue $queue) {
                    return 'QUEUE_NAME' === $queue->getQueueName();
                }),
                Mockery::on(function (PsrMessage $message) {
                    return 'MESSAGE' === $message->getBody();
                }),
            ])
            ->once();
        $context = Mockery::mock(NullContext::class)->makePartial();
        $context->shouldReceive('createProducer')->once()->andReturn($producer);
        $service = new RequeueAbandonedJobMessageService($context, $abandonedJobMessageStore);
        $service->execute(1);
        $this->assertCount(0, $abandonedJobMessageStore->all());
    }
}
