<?php

namespace Shippinno\Job\Test\Application\Job;

use Shippinno\Job\Application\Job\JobRunner;
use Shippinno\Job\Domain\Model\Job;
use Shippinno\Job\Domain\Model\JobFailedException;

class ExpendableJobRunner implements JobRunner
{
    /**
     * @param Job $job
     * @throws JobFailedException
     */
    public function run(Job $job): void
    {
        throw new JobFailedException;
    }
}
