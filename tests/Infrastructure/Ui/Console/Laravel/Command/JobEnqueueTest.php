<?php

namespace Shippinno\Job\Test\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Mockery;
use PHPUnit\Framework\TestCase;
use Shippinno\Job\Application\Messaging\EnqueueStoredJobsService;
use Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command\JobEnqueue;

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

    public function testIfServiceExecutedSuccessfully()
    {
        $enqueueStoredJobsService = Mockery::mock(EnqueueStoredJobsService::class);
        $enqueueStoredJobsService
            ->shouldReceive('execute')
            ->once()
            ->andReturn(5);
        $entityManager = Mockery::mock(EntityManager::class);
        $entityManager
            ->shouldReceive('clear')
            ->once()
            ->shouldReceive('flush')
            ->once();
        $managerRegistry = Mockery::mock(ManagerRegistry::class);
        $managerRegistry->shouldReceive(['getManager' => $entityManager]);
        $jobConsume = new JobEnqueue($enqueueStoredJobsService, $managerRegistry);
        $jobConsume->handle();
        $this->assertTrue(true);
    }
}
