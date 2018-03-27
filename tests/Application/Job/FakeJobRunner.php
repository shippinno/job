<?php

namespace Shippinno\Job\Application\Job;

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
