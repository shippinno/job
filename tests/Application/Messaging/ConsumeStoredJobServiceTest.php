<?php

namespace Shippinno\Job\Test\Application\Messaging;

use Enqueue\Null\NullMessage;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;
use Mockery;
use PHPUnit\Framework\TestCase;
use Shippinno\Job\Application\Job\JobRunnerRegistry;
use Shippinno\Job\Application\Messaging\ConsumeStoredJobService;
use Shippinno\Job\Domain\Model\AbandonedJobMessageStore;
use Shippinno\Job\Domain\Model\Job;
use Shippinno\Job\Domain\Model\JobSerializer;
use Shippinno\Job\Domain\Model\JobStore;
use Shippinno\Job\Domain\Model\StoredJob;
use Shippinno\Job\Domain\Model\StoredJobSerializer;
use Shippinno\Job\Test\Application\Job\FakeJobRunner;
use Shippinno\Job\Test\Application\Job\SucceedingJobRunner;
use Shippinno\Job\Test\Domain\Model\DependedNullJob;
use Shippinno\Job\Test\Domain\Model\FakeJob;
use Shippinno\Job\Test\Domain\Model\NullJob;
use Shippinno\Job\Test\Domain\Model\SimpleJobSerializer;
use Shippinno\Job\Test\Domain\Model\SimpleStoredJobSerializer;
use Shippinno\Job\Test\Infrastructure\Domain\Model\InMemoryAbandonedJobMessageStore;

class ConsumeStoredJobServiceTest extends TestCase
{
    /**
     * @var JobSerializer
     */
    private $jobSerializer;

    /**
     * @var StoredJobSerializer
     */
    private $storedJobSerializer;

    /**
     * @var AbandonedJobMessageStore
     */
    private $abandonedJobMessageStore;

    public function setUp()
    {
        $this->storedJobSerializer = new SimpleStoredJobSerializer;
        $this->jobSerializer = new SimpleJobSerializer;
        $this->abandonedJobMessageStore = new InMemoryAbandonedJobMessageStore;
    }

    public function testItShouldThrowExceptionIfJobRunnerNotRegistered()
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
        $service = $this->createService();
        $service->execute($consumer);
        $this->assertCount(1, $this->abandonedJobMessageStore->all());
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
        $service = $this->createService();
        $service->execute($consumer);
        $this->assertTrue(true);
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
        $service = $this->createService(count($job->dependentJobs()));
        $service->execute($consumer);
        $this->assertCount(0, $this->abandonedJobMessageStore->all());
    }

    public function testItShouldRequeueIfFailedAndMaxAttemptsNotExceeded()
    {
        $job = new FakeJob(true);
        $job->setReattemptDelay(0);
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
        $service = $this->createService();
        $service->execute($consumer);
        $this->assertCount(0, $this->abandonedJobMessageStore->all());
    }

    public function testItShouldRejectIfFailedAndMaxAttemtsExceeded()
    {
        $job = new FakeJob(true);
        $identifier = uniqid();
        $message = $this->createMessage($identifier, $job, $job->maxAttempts());
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
        $service = $this->createService();
        $service->execute($consumer);
        $this->assertCount(1, $this->abandonedJobMessageStore->all());
    }

    private function createMessage(string $identifier, Job $job, int $attempts = null): PsrMessage
    {
        $storedJob = new StoredJob(get_class($job), $this->jobSerializer->serialize($job), $job->createdAt());
        $message = new NullMessage($this->storedJobSerializer->serialize($storedJob));
        $message->setProperty('identifier', $identifier);
        if (null !== $attempts) {
            $message->setProperty('attempts', $attempts);
        }

        return $message;
    }

    private function createConsumer(PsrMessage $message)
    {
        $consumer = Mockery::mock(PsrConsumer::class);
        $consumer
            ->shouldReceive('receive')
            ->once()
            ->andReturn($message);

        return $consumer;
    }

    private function createService(int $dependentJobsCount = 0): ConsumeStoredJobService
    {
        $storedJobSerializer = new SimpleStoredJobSerializer;
        $jobSerializer = new SimpleJobSerializer;
        $jobRunnerRegistry = new JobRunnerRegistry;
        $jobRunnerRegistry->register([
            NullJob::class => new SucceedingJobRunner,
            DependedNullJob::class => new SucceedingJobRunner,
            FakeJob::class => new FakeJobRunner,
        ]);
        $jobStore = Mockery::mock(JobStore::class);
        if ($dependentJobsCount > 0) {
            $jobStore->shouldReceive('append')->times($dependentJobsCount);
        }
        $service = new ConsumeStoredJobService(
            $storedJobSerializer,
            $jobSerializer,
            $jobRunnerRegistry,
            $jobStore,
            $this->abandonedJobMessageStore
        );

        return $service;
    }
}

class UnknownJob extends Job
{
}
