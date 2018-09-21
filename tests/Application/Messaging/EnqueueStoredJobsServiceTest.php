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
use Shippinno\Job\Application\Messaging\JobFlightManager;
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
        $storedJobSerializer = new SimpleStoredJobSerializer;
        $enqueuedStoredJobTrackerStore = Mockery::mock(EnqueuedStoredJobTrackerStore::class);
        $jobFlightManager = Mockery::mock(JobFlightManager::class);
        $jobFlightManager
            ->shouldReceive('preBoardingJobFlights')
            ->with('TOPIC')
            ->andReturn([]);
        $service = new EnqueueStoredJobsService(
            $context,
            $jobStore,
            $storedJobSerializer,
            $enqueuedStoredJobTrackerStore,
            $jobFlightManager
        );
        $this->assertSame(0, $service->execute('TOPIC'));
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
            ->shouldReceive('storedJobsOfIds')
            ->withArgs([[1, 2]])
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
        $jobFlightManager = Mockery::mock(JobFlightManager::class);
        $jobFlightManager
            ->shouldReceive('preBoardingJobFlights')
            ->with('TOPIC')
            ->andReturn([1, 2])
            ->shouldReceive('boarding')->once()->with(1)
            ->shouldReceive('boarding')->once()->with(2)
            ->shouldReceive('departed')->once()->with(1)
            ->shouldReceive('departed')->once()->with(2);
        $service = new EnqueueStoredJobsService(
            $context,
            $jobStore,
            $storedJobSerializer,
            $enqueuedStoredJobTrackerStore,
            $jobFlightManager
        );
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
        $jobStore
            ->shouldReceive('storedJobsOfIds')
            ->withArgs([[1, 2]])
            ->andReturn($storedJobs);
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
        $jobFlightManager = Mockery::mock(JobFlightManager::class);
        $jobFlightManager
            ->shouldReceive('preBoardingJobFlights')
            ->with('TOPIC')
            ->andReturn([1, 2])
            ->shouldReceive('boarding')->once()->with(1)
            ->shouldReceive('departed')->once()->with(1);
        $service = new EnqueueStoredJobsService(
            $context,
            $jobStore,
            $storedJobSerializer,
            $enqueuedStoredJobTrackerStore,
            $jobFlightManager
        );
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
            ->shouldReceive('storedJobsOfIds')
            ->withArgs([[1, 2]])
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
        $jobFlightManager = Mockery::mock(JobFlightManager::class);
        $jobFlightManager
            ->shouldReceive('preBoardingJobFlights')
            ->with('TOPIC')
            ->andReturn([1, 2])
            ->shouldReceive('boarding')->once()->with(1)
            ->shouldReceive('boarding')->once()->with(2)
            ->shouldReceive('departed')->once()->with(1)
            ->shouldReceive('departed')->once()->with(2);
        $service = new EnqueueStoredJobsService(
            $context,
            $jobStore,
            $storedJobSerializer,
            $enqueuedStoredJobTrackerStore,
            $jobFlightManager
        );
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
            ->shouldReceive('storedJobsOfIds')
            ->withArgs([[1, 2]])
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
        $jobFlightManager = Mockery::mock(JobFlightManager::class);
        $jobFlightManager
            ->shouldReceive('preBoardingJobFlights')
            ->with('TOPIC')
            ->andReturn([1, 2])
            ->shouldReceive('boarding')->once()->with(1)
            ->shouldReceive('boarding')->once()->with(2)
            ->shouldReceive('departed')->once()->with(1)
            ->shouldReceive('departed')->once()->with(2);
        $service = new EnqueueStoredJobsService(
            $context,
            $jobStore,
            $storedJobSerializer,
            $enqueuedStoredJobTrackerStore,
            $jobFlightManager
        );
        try {
            $service->execute('TOPIC');
        } catch (FailedToEnqueueStoredJobException $e) {
            $this->assertTrue(true);
        }

    }

    /** @test */
    public function ストアされた1万ものJobをエンキューする()
    {
        // TODO: 1万回アサートにより9秒かかるテストになってしまっています

        // Arrange
        $storedJobs = $this->create10ThousandsOfFakeStoredJobs();
        $context = new NullContext();
        $jobStore = Mockery::mock(JobStore::class);
        $jobStore
            ->shouldReceive('storedJobsOfIds')
            ->withArgs([range(1, 10000)])
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
                    return 10000 === $storedJob->id();
                })
            ]);

        //1万件のJobそれぞれに対してDBにStoreされるようになっているか
        $calledBoarding = 0;
        $calledDeparted = 0;
        $jobFlightManager = Mockery::mock(JobFlightManager::class);
        $jobFlightManager
            ->shouldReceive('preBoardingJobFlights')
            ->with('TOPIC')
            ->andReturn(range(1, 10000))
            ->shouldReceive('boarding')
            ->withArgs(function (string $messageId) use (&$calledBoarding) {
                $calledBoarding++;
                return $messageId === "$calledBoarding";
            })
            ->shouldReceive('departed')
            ->withArgs(function (string $messageId) use (&$calledDeparted) {
                $calledDeparted++;
                return $messageId === "$calledDeparted";
            });
        $service = new EnqueueStoredJobsService(
            $context,
            $jobStore,
            $storedJobSerializer,
            $enqueuedStoredJobTrackerStore,
            $jobFlightManager
        );

        // Act & Assert
        $this->assertSame(10000, $service->execute('TOPIC'));
    }

    /** @test */
    public function ストアされた1万ものJobをエンキューするintoSQS()
    {
        // TODO: 1万回アサートにより10秒かかるテストになってしまっています

        // Arrange
        $storedJobs = $this->create10ThousandsOfFakeStoredJobs();
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
            ->shouldReceive('storedJobsOfIds')
            ->withArgs([range(1, 10000)])
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
                    return 10000 === $storedJob->id();
                })
            ]);

        // 1万件のJobそれぞれに対してDBにStoreされるようになっているか TODO: 10秒かかる
        $calledBoarding = 0;
        $calledDeparted = 0;
        $jobFlightManager = Mockery::mock(JobFlightManager::class);
        $jobFlightManager
            ->shouldReceive('preBoardingJobFlights')
            ->with('TOPIC')
            ->andReturn(range(1, 10000))
            ->shouldReceive('boarding')
            ->withArgs(function (string $messageId) use (&$calledBoarding) {
                $calledBoarding++;
                return $messageId === "$calledBoarding";
            })
            ->shouldReceive('departed')
            ->withArgs(function (string $messageId) use (&$calledDeparted) {
                $calledDeparted++;
                return $messageId === "$calledDeparted";
            });

        $service = new EnqueueStoredJobsService(
            $context,
            $jobStore,
            $storedJobSerializer,
            $enqueuedStoredJobTrackerStore,
            $jobFlightManager
        );

        // Act & Assert
        $this->assertSame(10000, $service->execute('TOPIC'));
    }

    private function create10ThousandsOfFakeStoredJobs()
    {
        $storedJobs = [];
        for($i = 1; $i < 10001; $i++) {
            $storedJobs[] = new FakeStoredJob("name", 'body', new DateTimeImmutable, $i);
        }
        return $storedJobs;
    }
}
