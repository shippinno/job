<?php

namespace Shippinno\Job\Application\Job;

use Shippinno\Job\Domain\Model\JobNotRegisteredException;

class JobRunnerRegistry
{
    /**
     * @var array
     */
    protected $jobRunners = [];

    /**
     * @param array $jobRunners
     */
    public function register(array $jobRunners): void
    {
        foreach ($jobRunners as $jobName => $jobRunner) {
            $this->jobRunners[$jobName] = $jobRunner;
        }
    }

    /**
     * @param string $jobName
     * @return JobRunner
     * @throws JobNotRegisteredException
     */
    public function get(string $jobName): JobRunner
    {
        if (!isset($this->jobRunners[$jobName])) {
            throw new JobNotRegisteredException;
        }

        return $this->jobRunners[$jobName];
    }
}
