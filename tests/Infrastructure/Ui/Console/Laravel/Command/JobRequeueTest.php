<?php

namespace Shippinno\Job\Test\Infrastructure\Ui\Console\Laravel\Command;

use Enqueue\Null\NullContext;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use Shippinno\Job\Application\Messaging\RequeueAbandonedJobMessageService;
use Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command\JobRequeue;
use Shippinno\Job\Test\Infrastructure\Domain\Model\InMemoryAbandonedJobMessageStore;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Tests\Fixtures\DummyOutput;

class JobRequeueTest extends TestCase
{
    /**
     * @expectedException \Shippinno\Job\Domain\Model\AbandonedJobMessageNotFoundException
     */
    public function test()
    {
        $inputDefinition = new InputDefinition([new InputArgument('id')]);
        $input = new ArrayInput(['id' => 1], $inputDefinition);
        $output = new DummyOutput;
        $jobRequeue = new JobRequeue(
            new RequeueAbandonedJobMessageService(new NullContext, new InMemoryAbandonedJobMessageStore)
        );
        $jobRequeue->setLaravel(new Container);
        $jobRequeue->run($input, $output);
    }
}
