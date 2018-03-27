<?php

namespace Shippinno\Job\Application\Job;

use Shippinno\Job\Domain\Model\JobFailedException;

abstract class JobRunner
{
    /**
     * @param Job $job
     * @throws JobFailedException
     */
    abstract public function run(Job $job): void;
}
