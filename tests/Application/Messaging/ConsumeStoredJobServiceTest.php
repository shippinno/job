<?php

namespace Shippinno\Job\Test\Application\Messaging;

use Enqueue\Null\NullMessage;
use Enqueue\Sqs\SqsMessage;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Queue;
use Mockery;
use PHPUnit\Framework\TestCase;
use Shippinno\Job\Application\Job\JobRunnerRegistry;
use Shippinno\Job\Application\Messaging\ConsumeStoredJobService;
use Shippinno\Job\Domain\Model\Job;
use Shippinno\Job\Domain\Model\JobSerializer;
use Shippinno\Job\Domain\Model\JobStore;
use Shippinno\Job\Domain\Model\StoredJob;
use Shippinno\Job\Domain\Model\StoredJobSerializer;
use Shippinno\Job\Test\Application\Job\ExpendableJobRunner;
use Shippinno\Job\Test\Application\Job\FakeJobRunner;
use Shippinno\Job\Test\Application\Job\SucceedingJobRunner;
use Shippinno\Job\Test\Domain\Model\DependedNullJob;
use Shippinno\Job\Test\Domain\Model\ExpendableJob;
use Shippinno\Job\Test\Domain\Model\FakeJob;
use Shippinno\Job\Test\Domain\Model\NullJob;
use Shippinno\Job\Test\Domain\Model\SimpleJobSerializer;
use Shippinno\Job\Test\Domain\Model\SimpleStoredJobSerializer;
use WMDE\PsrLogTestDoubles\LoggerSpy;

class ConsumeStoredJobServiceTest extends TestCase
{
    private const QUEUE_NAME = 'QUEUE_NAME';

    /**
     * @var JobSerializer
     */
    private $jobSerializer;

    /**
     * @var StoredJobSerializer
     */
    private $storedJobSerializer;

    public function setUp(): void
    {
        $this->storedJobSerializer = new SimpleStoredJobSerializer;
        $this->jobSerializer = new SimpleJobSerializer;
    }

    public function testThatNothingIsDoneIfReceivesNullMessage()
    {
        $consumer = $this->createConsumer(null);
        $context = $this->createContext($consumer);
        $service = $this->createService($context);
        $service->execute(self::QUEUE_NAME);
        $this->assertTrue(true);
    }

    public function testItShouldRejectAndAbandoneIfJobRunnerNotRegistered()
    {
        $job = new UnknownJob;
        $identifier = uniqid();
        $message = $this->createMessage($identifier, $job);
        $consumer = $this->createConsumer($message);
        $consumer
            ->shouldReceive('reject')
            ->once()
            ->withArgs([
                Mockery::on(function (NullMessage $message) use ($identifier) {
                    return $message->getProperty('identifier') === $identifier;
                })
            ])
            ->once();
        $context = $this->createContext($consumer);
        $service = $this->createService($context);
        $service->execute(self::QUEUE_NAME);
        $this->assertTrue(true);
    }

    public function testItShouldAcknowledgeIfSucceeded()
    {
        $job = new NullJob;
        $identifier = uniqid();
        $message = $this->createMessage($identifier, $job);
        $consumer = $this->createConsumer($message);
        $consumer
            ->shouldReceive('acknowledge')
            ->once()
            ->withArgs([
                Mockery::on(function (NullMessage $message) use ($identifier) {
                    return $message->getProperty('identifier') === $identifier;
                })
            ])
            ->once();
        $context = $this->createContext($consumer);
        $service = $this->createService($context);
        $persistCalled = false;
        $service->execute(self::QUEUE_NAME, function () use (&$persistCalled) {
            $persistCalled = true;
            return true;
        });
        $this->assertTrue($persistCalled);
    }

    public function testItShouldAcknowledgeAndStoreDependentJobIfSucceeded()
    {
        $job = new DependedNullJob;
        $identifier = uniqid();
        $message = $this->createMessage($identifier, $job);
        $consumer = $this->createConsumer($message);
        $consumer
            ->shouldReceive('acknowledge')
            ->once()
            ->withArgs([
                Mockery::on(function (NullMessage $message) use ($identifier) {
                    return $message->getProperty('identifier') === $identifier;
                })
            ])
            ->once();
        $context = $this->createContext($consumer);
        $service = $this->createService($context, count($job->dependentJobs()));
        $service->execute(self::QUEUE_NAME);
        $this->assertTrue(true);
    }

    public function testItShouldRequeueIfFailedToPersist()
    {
        $job = new NullJob;
        $identifier = uniqid();
        $message = $this->createMessage($identifier, $job);
        $consumer = $this->createConsumer($message);
        $consumer
            ->shouldReceive('reject')
            ->once()
            ->withArgs([
                Mockery::on(function (NullMessage $message) use ($identifier) {
                    return $message->getProperty('identifier') === $identifier;
                }),
                true
            ])
            ->once();
        $context = $this->createContext($consumer);
        $service = $this->createService($context);
        // $service->execute(self::QUEUE_NAME, function () {
        //     return false;
        // });
        $this->assertTrue(true);
    }

    public function testItShouldRequeueIfFailedAndMaxAttemptsNotExceeded()
    {
        $reattemptDelay = 600;
        $job = new FakeJob(true);
        $job->setReattemptDelay($reattemptDelay);
        $job->setMaxAttempts(2);
        $identifier = uniqid();
        $message = $this->createMessage($identifier, $job, 0, SqsMessage::class);
        $consumer = $this->createConsumer($message);
        $consumer
            ->shouldReceive('acknowledge')
            ->once()
            ->withArgs([
                Mockery::on(function (SqsMessage $message) use ($identifier) {
                    return $message->getProperty('identifier') === $identifier;
                })
            ]);
        $context = $this->createContext($consumer);
        $service = $this->createService($context);
        $service->execute(self::QUEUE_NAME);
        $this->assertTrue(true);
    }

    public function testItShouldRejectIfFailedAndMaxAttemtsExceeded()
    {
        $job = new FakeJob(true);
        $identifier = uniqid();
        $message = $this->createMessage($identifier, $job, $job->maxAttempts());
        $consumer = $this->createConsumer($message);
        $consumer
            ->shouldReceive('acknowledge')
            ->once()
            ->withArgs([
                Mockery::on(function (NullMessage $message) use ($identifier) {
                    return $message->getProperty('identifier') === $identifier;
                })
            ]);
        $context = $this->createContext($consumer);
        $service = $this->createService($context);
        $service->execute(self::QUEUE_NAME);
        $this->assertTrue(true);
    }

    public function testItShouldLetExpendableJobGo()
    {
        $job = new ExpendableJob();
        $identifier = uniqid();
        $message = $this->createMessage($identifier, $job);
        $consumer = $this->createConsumer($message);
        $consumer
            ->shouldReceive('acknowledge')
            ->once()
            ->withArgs([
                Mockery::on(function (NullMessage $message) use ($identifier) {
                    return $message->getProperty('identifier') === $identifier;
                })
            ]);
        $context = $this->createContext($consumer);
        $service = $this->createService($context);
        $logger = new LoggerSpy;
        $service->setLogger($logger);
        $service->execute(self::QUEUE_NAME);
        $this->assertSame('Job failed but acknowledging message', $logger->getFirstLogCall()->getMessage());
    }

    private function createMessage(string $identifier, Job $job, int $attempts = null, string $messageClass = NullMessage::class): Message
    {
        $storedJob = new StoredJob(get_class($job), $this->jobSerializer->serialize($job), $job->createdAt(), $job->isExpendable());
        $message = new $messageClass($this->storedJobSerializer->serialize($storedJob));
        $message->setMessageId(uniqid());
        $message->setProperty('identifier', $identifier);
        if (null !== $attempts) {
            $message->setProperty('attempts', $attempts);
        }

        return $message;
    }

    private function createConsumer(?Message $message)
    {
        $consumer = Mockery::mock(Consumer::class);
        $consumer
            ->shouldReceive('receive')
            ->once()
            ->andReturn($message);

        return $consumer;
    }

    private function createContext(Consumer $consumer)
    {
        $queue = Mockery::mock(Queue::class);
        $context = Mockery::mock(Context::class);
        $context
            ->shouldReceive('createQueue')
            ->once()
            ->withArgs([self::QUEUE_NAME])
            ->andReturn($queue)
            ->shouldReceive('createConsumer')
            ->once()
            ->withArgs([
                Mockery::on(function (Queue $argument) use ($queue) {
                    return $argument === $queue;
                })
            ])
            ->andReturn($consumer);
        return $context;
    }

    private function createService(Context $context, int $dependentJobsCount = 0): ConsumeStoredJobService
    {
        $storedJobSerializer = new SimpleStoredJobSerializer;
        $jobSerializer = new SimpleJobSerializer;
        $jobRunnerRegistry = new JobRunnerRegistry;
        $jobRunnerRegistry->register([
            NullJob::class => new SucceedingJobRunner,
            DependedNullJob::class => new SucceedingJobRunner,
            FakeJob::class => new FakeJobRunner,
            ExpendableJob::class => new ExpendableJobRunner,
        ]);
        $jobStore = Mockery::mock(JobStore::class);
        if ($dependentJobsCount > 0) {
            $jobStore->shouldReceive('append')->times($dependentJobsCount);
        }
        $service = new ConsumeStoredJobService(
            $context,
            $storedJobSerializer,
            $jobSerializer,
            $jobRunnerRegistry,
            $jobStore
        );

        return $service;
    }
}

class UnknownJob extends Job
{
}
