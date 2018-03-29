<?php

namespace Shippinno\Job\Domain\Model;

class JobRunnerNotRegisteredException extends Exception
{
    public function __construct(string $jobName)
    {
        parent::__construct(sprintf('No JobRunner is registered for %s', $jobName));
    }
}
