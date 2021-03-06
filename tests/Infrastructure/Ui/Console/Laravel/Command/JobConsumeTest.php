<?php

namespace Shippinno\Job\Test\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Mockery;
use PHPUnit\Framework\TestCase;
use Shippinno\Job\Application\Messaging\ConsumeStoredJobService;
use Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command\JobConsume;

class JobConsumeTest extends TestCase
{
    public function setUp()
    {
        putenv('JOB_CONSUME_QUEUE=test');
    }

    public function tearDown()
    {
        putenv('JOB_CONSUME_QUEUE=');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The env JOB_CONSUME_QUEUE is not defined
     */
    public function testItShouldThrowLogicExceptionIfEnvNotSet()
    {
        putenv('JOB_CONSUME_QUEUE=');
        $jobConsume = new JobConsume(
            Mockery::mock(ConsumeStoredJobService::class),
            Mockery::mock(ManagerRegistry::class)
        );
        $jobConsume->handle();
    }

    public function testIfServiceExecutedSuccessfully()
    {
        $consumeStoredJobService = Mockery::mock(ConsumeStoredJobService::class);
        $consumeStoredJobService
            ->shouldReceive('execute')
            ->once();
//        $entityManager = Mockery::mock(EntityManager::class);
//        $entityManager
//            ->shouldReceive('clear')
//            ->once()
//            ->shouldReceive('flush')
//            ->once();
//        $managerRegistry = Mockery::mock(ManagerRegistry::class);
//        $managerRegistry->shouldReceive(['getManagers' => [$entityManager]]);
        $jobConsume = new JobConsume($consumeStoredJobService);
        $jobConsume->handle();
        $this->assertTrue(true);
    }
}
