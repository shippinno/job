<?php

namespace Shippinno\Job\Application\Job;

use Shippinno\Job\Domain\Model\JobRunnerNotRegisteredException;

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
     * @throws JobRunnerNotRegisteredException
     */
    public function get(string $jobName): JobRunner
    {
        if (!isset($this->jobRunners[$jobName])) {
            throw new JobRunnerNotRegisteredException($jobName);
        }

        return $this->jobRunners[$jobName];
    }
}
