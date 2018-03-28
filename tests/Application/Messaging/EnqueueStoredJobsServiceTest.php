<?php

namespace Shippinno\Job\Test\Application\Messaging;

use DateTimeImmutable;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullProducer;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Mockery;
use PHPUnit\Framework\TestCase;
use Shippinno\Job\Application\Messaging\EnqueuedStoredJobTrackerStore;
use Shippinno\Job\Application\Messaging\EnqueueStoredJobsService;
use Shippinno\Job\Domain\Model\JobStore;
use Shippinno\Job\Test\Domain\Model\FakeStoredJob;

class EnqueueStoredJobsServiceTest extends TestCase
{
    public function testItReturnsZeroIfNoStoredJobsToEnqueue()
    {
        $context = new NullContext;
        $jobStore = Mockery::mock(JobStore::class);
        $jobStore->shouldReceive(['storedJobsSince' => []]);
        $enqueuedStoredJobTrackerStore = Mockery::mock(EnqueuedStoredJobTrackerStore::class);
        $enqueuedStoredJobTrackerStore->shouldReceive(['lastEnqueuedStoredJobId' => 0]);
        $service = new EnqueueStoredJobsService($context, $jobStore, $enqueuedStoredJobTrackerStore);
        $this->assertSame(0, $service->execute('topic_name'));
    }

    public function testEnqueueTwoStoredJobs()
    {
        $storedJobs = [
            new FakeStoredJob('name', 'body', new DateTimeImmutable, 1),
            new FakeStoredJob('name', 'body', new DateTimeImmutable, 2)
        ];
        $context = new NullContext();
        $jobStore = Mockery::mock(JobStore::class);
        $jobStore
            ->shouldReceive('storedJobsSince')
            ->withArgs([null])
            ->once()
            ->andReturn($storedJobs);
        $enqueuedStoredJobTrackerStore = Mockery::mock(EnqueuedStoredJobTrackerStore::class);
        $enqueuedStoredJobTrackerStore
            ->shouldReceive(['lastEnqueuedStoredJobId' => null])
            ->shouldReceive('trackLastEnqueuedStoredJob')
            ->once()
            ->withArgs([
                Mockery::on(function (string $topic) {
                    return 'TOPIC' === $topic;
                }),
                Mockery::on(function (FakeStoredJob $storedJob) {
                    return 2 === $storedJob->id();
                })
            ]);
        $service = new EnqueueStoredJobsService($context, $jobStore, $enqueuedStoredJobTrackerStore);
        $this->assertSame(2, $service->execute('TOPIC'));
    }

    /**
     * @expectedException  \Shippinno\Job\Domain\Model\FailedToEnqueueStoredJobException
     */
    public function testIfFailedToEnqueueSecondStoredJob()
    {
        $storedJobs = [
            new FakeStoredJob('name', 'body', new DateTimeImmutable, 1),
            new FakeStoredJob('name', 'body', new DateTimeImmutable, 2)
        ];
        $context = new ContextThatCreatesProducerThatFailsToSendSecondMessage();
        $jobStore = Mockery::mock(JobStore::class);
        $jobStore->shouldReceive([
            'storedJobsSince' => $storedJobs,
        ]);
        $enqueuedStoredJobTrackerStore = Mockery::mock(EnqueuedStoredJobTrackerStore::class);
        $enqueuedStoredJobTrackerStore
            ->shouldReceive(['lastEnqueuedStoredJobId' => 0])
            ->shouldReceive('trackLastEnqueuedStoredJob')
            ->once()
            ->withArgs([
                Mockery::on(function (string $topic) {
                    return 'TOPIC' === $topic;
                }),
                Mockery::on(function (FakeStoredJob $storedJob) {
                    return 1 === $storedJob->id();
                })
            ]);

        $service = new EnqueueStoredJobsService($context, $jobStore, $enqueuedStoredJobTrackerStore);
        $service->execute('TOPIC');
    }
}

class ContextThatCreatesProducerThatFailsToSendSecondMessage extends NullContext
{
    public function createProducer()
    {
        return new ProducerThatFailsToSendSecondMessage;
    }
}

class ProducerThatFailsToSendSecondMessage extends NullProducer
{
    public function send(PsrDestination $destination, PsrMessage $message)
    {
        if (2 === json_decode($message->getBody())->id) {
            throw new InvalidMessageException();
        }
        parent::send($destination, $message);
    }
}