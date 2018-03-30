<?php

namespace Shippinno\Job\Test\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Illuminate\Container\Container;
use Mockery;
use PHPUnit\Framework\TestCase;
use Shippinno\Job\Application\Messaging\RequeueAbandonedJobMessageService;
use Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command\JobAbandonedRequeue;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Tests\Fixtures\DummyOutput;

class JobAbandonedRequeueTest extends TestCase
{
    public function testThatServiceIsExecuted()
    {
        $entityManager = Mockery::mock(EntityManager::class);
        $entityManager->shouldReceive('flush')->once();
        $managerRegistry = Mockery::mock(ManagerRegistry::class);
        $managerRegistry->shouldReceive(['getManager' => $entityManager]);
        $service = Mockery::mock(RequeueAbandonedJobMessageService::class);
        $service->shouldReceive('execute')->once()->withArgs([1]);
        $command = new JobAbandonedRequeue($service, $managerRegistry);
        $command->setLaravel(new Container);
        $inputDefinition = new InputDefinition([new InputArgument('id')]);
        $input = new ArrayInput(['id' => '1'], $inputDefinition);
        $output = new DummyOutput;
        $command->run($input, $output);
        $this->assertTrue(true);
    }
}
