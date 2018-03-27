<?php

namespace Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command;

use PHPUnit\Framework\TestCase;

class JobEnqueueTest extends TestCase
{
    public function test()
    {
        $jobConsume = new JobEnqueue(
            new NullContext,
            $this->serializer(),
            $this->initEntityManager(),
            $this->container([FakeJobRunner::class, $jobRunner])
        );
    }
}
