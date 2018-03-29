<?php

namespace Shippinno\Job\Application\Job;

use Shippinno\Job\Domain\Model\Job;
use Shippinno\Job\Domain\Model\JobFailedException;

interface JobRunner
{
    /**
     * @param Job $job
     * @throws JobFailedException
     */
    public function run(Job $job): void;
}
