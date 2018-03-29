<?php

namespace Shippinno\Job\Test\Application\Job;

use Shippinno\Job\Application\Job\JobRunner;
use Shippinno\Job\Domain\Model\Job;

class SucceedingJobRunner implements JobRunner
{
    /**
     * {@inheritdoc}
     */
    public function run(Job $job): void
    {
    }
}