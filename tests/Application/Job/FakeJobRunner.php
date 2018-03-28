<?php

namespace Shippinno\Job\Test\Application\Job;

use Shippinno\Job\Application\Job\JobRunner;
use Shippinno\Job\Domain\Model\FakeJob;
use Shippinno\Job\Domain\Model\Job;
use Shippinno\Job\Domain\Model\JobFailedException;

class FakeJobRunner extends JobRunner
{
    /**
     * {@inheritdoc}
     * @param FakeJob $job
     */
    public function run(Job $job): void
    {
        if ($job->fails()) {
            throw new JobFailedException;
        }
    }
}