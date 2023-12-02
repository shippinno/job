<?php

namespace Shippinno\Job\Test\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Illuminate\Container\Container;
use Mockery;
use PHPUnit\Framework\TestCase;
use Shippinno\Job\Application\Messaging\DeleteAbandonedJobMessageService;
use Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command\JobAbandonedDelete;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\BufferedOutput;

class JobAbandonedDeleteTest extends TestCase
{
    public function testThatServiceIsExecuted()
    {
//        $entityManager = Mockery::mock(EntityManager::class);
//        $entityManager->shouldReceive('flush')->once();
//        $managerRegistry = Mockery::mock(ManagerRegistry::class);
//        $managerRegistry->shouldReceive(['getManagers' => [$entityManager]]);
        $service = Mockery::mock(DeleteAbandonedJobMessageService::class);
        $service->shouldReceive('execute')->once()->withArgs([1]);
        $command = new JobAbandonedDelete($service);
        $container = Mockery::mock(Container::class)->makePartial();
        $container->shouldReceive('runningUnitTests')->andReturn(true);
        $command->setLaravel($container);
        $inputDefinition = new InputDefinition([new InputArgument('id')]);
        $input = new ArrayInput(['id' => '1'], $inputDefinition);
        $output = new BufferedOutput();
        $command->run($input, $output);
        $this->assertTrue(true);
    }
}
