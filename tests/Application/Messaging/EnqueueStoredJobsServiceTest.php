<?php

namespace Shippinno\Job\Test\Application\Messaging;

use DateTimeImmutable;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullProducer;
use Enqueue\Null\NullTopic;
use Enqueue\Sqs\SqsContext;
use Enqueue\Sqs\SqsProducer;
use Exception;
use Interop\Queue\PsrContext;
use Mockery;
use PHPUnit\Framework\TestCase;
use Shippinno\Job\Application\Messaging\EnqueuedStoredJobTrackerStore;
use Shippinno\Job\Application\Messaging\EnqueueStoredJobsService;
use Shippinno\Job\Domain\Model\FailedToEnqueueStoredJobException;
use Shippinno\Job\Domain\Model\JobStore;
use Shippinno\Job\Domain\Model\StoredJob;
use Shippinno\Job\Test\Domain\Model\FakeStoredJob;
use Shippinno\Job\Test\Domain\Model\SimpleStoredJobSerializer;

class EnqueueStoredJobsServiceTest extends TestCase
{
    public function testItReturnsZeroIfNoStoredJobsToEnqueue()
    {
        $context = new NullContext;
        $jobStore = Mockery::mock(JobStore::class);
        $jobStore->shouldReceive(['storedJobsSince' => []]);
        $storedJobSerializer = new SimpleStoredJobSerializer;
        $enqueuedStoredJobTrackerStore = Mockery::mock(EnqueuedStoredJobTrackerStore::class);
        $enqueuedStoredJobTrackerStore->shouldReceive(['lastEnqueuedStoredJobId' => 0]);
        $service = new EnqueueStoredJobsService($context, $jobStore, $storedJobSerializer, $enqueuedStoredJobTrackerStore);
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
        $storedJobSerializer = new SimpleStoredJobSerializer;
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
        $service = new EnqueueStoredJobsService($context, $jobStore, $storedJobSerializer, $enqueuedStoredJobTrackerStore);
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
        $storedJobSerializer = new SimpleStoredJobSerializer;
        $producer = Mockery::mock(NullProducer::class);
        $producer
            ->shouldReceive('send')
            ->once()
            ->withArgs([
                Mockery::on(function (NullTopic $topic) {
                    return 'TOPIC' === $topic->getTopicName();
                }),
                Mockery::on(function (NullMessage $message) use ($storedJobSerializer) {
                    return 1 === $storedJobSerializer->deserialize($message->getBody())->id();
                }),
            ])
            ->shouldReceive('send')
            ->once()
            ->withArgs([
                Mockery::on(function (NullTopic $topic) {
                    return 'TOPIC' === $topic->getTopicName();
                }),
                Mockery::on(function (NullMessage $message) use ($storedJobSerializer) {
                    return 2 === $storedJobSerializer->deserialize($message->getBody())->id();
                }),
            ])
            ->andThrow(new Exception);
        $context = Mockery::mock(NullContext::class)->makePartial();
        $context
            ->shouldReceive('createProducer')
            ->once()
            ->andReturn($producer);
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
                Mockery::on(function (StoredJob $storedJob) {
                    return 1 === $storedJob->id();
                })
            ]);
        $service = new EnqueueStoredJobsService($context, $jobStore, $storedJobSerializer, $enqueuedStoredJobTrackerStore);
        $service->execute('TOPIC');
    }

    public function testEnqueueTwoStoredJobsIntoSqs()
    {
        $storedJobs = [
            new FakeStoredJob('name', 'body', new DateTimeImmutable, 1),
            new FakeStoredJob('name', 'body', new DateTimeImmutable, 2)
        ];
        $producer = Mockery::mock(SqsProducer::class);
        $producer
            ->shouldReceive('sendAll')
            ->once();
        $context = Mockery::mock(SqsContext::class)->makePartial();
        $context
            ->shouldReceive('createProducer')
            ->once()
            ->andReturn($producer);
        $jobStore = Mockery::mock(JobStore::class);
        $jobStore
            ->shouldReceive('storedJobsSince')
            ->withArgs([null])
            ->once()
            ->andReturn($storedJobs);
        $storedJobSerializer = new SimpleStoredJobSerializer;
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
        $service = new EnqueueStoredJobsService($context, $jobStore, $storedJobSerializer, $enqueuedStoredJobTrackerStore);
        $this->assertSame(2, $service->execute('TOPIC'));
    }

    public function testEnqueueTwoStoredJobsIntoSqsWithFailure()
    {
        $storedJobs = [
            new FakeStoredJob('name', 'body', new DateTimeImmutable, 1),
            new FakeStoredJob('name', 'body', new DateTimeImmutable, 2)
        ];
        $producer = Mockery::mock(SqsProducer::class);
        $producer
            ->shouldReceive('sendAll')
            ->once()
            ->andThrow(new \RuntimeException());
        $context = Mockery::mock(SqsContext::class)->makePartial();
        $context
            ->shouldReceive('createProducer')
            ->once()
            ->andReturn($producer);
        $jobStore = Mockery::mock(JobStore::class);
        $jobStore
            ->shouldReceive('storedJobsSince')
            ->withArgs([null])
            ->once()
            ->andReturn($storedJobs);
        $storedJobSerializer = new SimpleStoredJobSerializer;
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
        $service = new EnqueueStoredJobsService($context, $jobStore, $storedJobSerializer, $enqueuedStoredJobTrackerStore);
        try {
            $service->execute('TOPIC');
        } catch (FailedToEnqueueStoredJobException $e) {
            $this->assertTrue(true);
        }

    }
}
