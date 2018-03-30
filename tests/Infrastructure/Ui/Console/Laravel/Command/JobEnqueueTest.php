<?php

namespace Shippinno\Job\Test\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Illuminate\Container\Container;
use Mockery;
use PHPUnit\Framework\TestCase;
use Shippinno\Job\Application\Messaging\EnqueueStoredJobsService;
use Shippinno\Job\Domain\Model\FailedToEnqueueStoredJobException;
use Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command\JobEnqueue;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Tests\Fixtures\DummyOutput;
use WMDE\PsrLogTestDoubles\LoggerSpy;

class JobEnqueueTest extends TestCase
{
    public function setUp()
    {
        putenv('JOB_ENQUEUE_TOPIC=test');
    }

    public function tearDown()
    {
        putenv('JOB_ENQUEUE_TOPIC=');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The env JOB_ENQUEUE_TOPIC is not defined
     */
    public function testItShouldThrowLogicExceptionIfEnvNotSet()
    {
        putenv('JOB_ENQUEUE_TOPIC=');
        $jobEnqueue = new JobEnqueue(
            Mockery::mock(EnqueueStoredJobsService::class),
            Mockery::mock(ManagerRegistry::class)
        );
        $jobEnqueue->handle();
    }

    public function testItShouldSleepIfServiceFail()
    {
        $service = Mockery::mock(EnqueueStoredJobsService::class);
        $service
            ->shouldReceive('execute')
            ->once()
            ->andThrow(new FailedToEnqueueStoredJobException);
        $entityManager = Mockery::mock(EntityManager::class);
        $entityManager
            ->shouldReceive('clear')
            ->once();
        $managerRegistry = Mockery::mock(ManagerRegistry::class);
        $managerRegistry->shouldReceive(['getManager' => $entityManager]);
        $logger = new LoggerSpy;
        $command = new JobEnqueue($service, $managerRegistry, $logger);
        $command->setLaravel(new Container);
        $command->run(new ArrayInput([]), new DummyOutput);
        $this->assertSame(
            'Failed to enqueue stored job, retrying in 0 second(s).',
            $logger->getFirstLogCall()->getMessage()
        );
    }

    public function testThatServiceIsExecutedSuccessfully()
    {
        $service = Mockery::mock(EnqueueStoredJobsService::class);
        $service
            ->shouldReceive('execute')
            ->once()
            ->andReturn(1);
        $entityManager = Mockery::mock(EntityManager::class);
        $entityManager
            ->shouldReceive('clear')
            ->once()
            ->shouldReceive('flush')
            ->once();
        $managerRegistry = Mockery::mock(ManagerRegistry::class);
        $managerRegistry->shouldReceive(['getManager' => $entityManager]);
        $command = new JobEnqueue($service, $managerRegistry);
        $command->setLaravel(new Container);
        $command->run(new ArrayInput([]), new DummyOutput);
        $this->assertTrue(true);
    }
}
