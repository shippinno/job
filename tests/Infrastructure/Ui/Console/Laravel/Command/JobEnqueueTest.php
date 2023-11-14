<?php

namespace Shippinno\Job\Test\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Illuminate\Container\Container;
use LogicException;
use Mockery;
use PHPUnit\Framework\TestCase;
use Shippinno\Job\Application\Messaging\EnqueueStoredJobsService;
use Shippinno\Job\Domain\Model\FailedToEnqueueStoredJobException;
use Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command\JobEnqueue;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tests\Fixtures\DummyOutput;
use WMDE\PsrLogTestDoubles\LoggerSpy;

class JobEnqueueTest extends TestCase
{
    public function setUp(): void
    {
        putenv('JOB_ENQUEUE_TOPIC=test');
    }

    public function tearDown(): void
    {
        putenv('JOB_ENQUEUE_TOPIC=');
    }

    /**
     * @expectException \LogicException
     * @expectExceptionMessage The env JOB_ENQUEUE_TOPIC is not defined
     */
    public function testItShouldThrowLogicExceptionIfEnvNotSet()
    {
        $this->expectException(LogicException::class);
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
            ->andThrow(new FailedToEnqueueStoredJobException(3));
//        $entityManager = Mockery::mock(EntityManager::class);
//        $entityManager->shouldReceive('flush')->once();
//        $entityManager
//            ->shouldReceive('clear')
//            ->once();
//        $managerRegistry = Mockery::mock(ManagerRegistry::class);
//        $managerRegistry->shouldReceive(['getManagers' => [$entityManager]]);
        $logger = new LoggerSpy;
        $command = new JobEnqueue($service, null, $logger);
        $command->setLaravel(new Container);
        $command->run(new ArrayInput([]), new BufferedOutput());
        $logCalls = $logger->getLogCalls()->getIterator();
        $this->assertSame(
            '3 job(s) enqueued.',
            $logCalls[0]->getMessage()
        );
        $this->assertSame(
            'Failed to enqueue stored job, retrying in 0 second(s).',
            $logCalls[1]->getMessage()
        );
    }

    public function testThatServiceIsExecutedSuccessfully()
    {
        $service = Mockery::mock(EnqueueStoredJobsService::class);
        $service
            ->shouldReceive('execute')
            ->once()
            ->andReturn(1);
//        $entityManager = Mockery::mock(EntityManager::class);
//        $entityManager
//            ->shouldReceive('clear')
//            ->once()
//            ->shouldReceive('flush')
//            ->once();
//        $managerRegistry = Mockery::mock(ManagerRegistry::class);
//        $managerRegistry->shouldReceive(['getManagers' => [$entityManager]]);
        $command = new JobEnqueue($service);
        $command->setLaravel(new Container);
        $command->run(new ArrayInput([]), new BufferedOutput());
        $this->assertTrue(true);
    }
}
