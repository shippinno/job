<?php

namespace Shippinno\Job\Application\Messaging;

interface JobFlightManager
{
    /**
     * @param int $jobId
     * @param string $jobName
     * @param string $queue
     */
    public function departed(int $jobId, string $jobName, string $queue): void;

    /**
     * @param int $jobId
     * @return null|JobFlight
     */
    public function latestJobFlightOfJobId(int $jobId): ?JobFlight;

    /**
     * @param string $jobId
     */
    public function acknowledged(string $jobId): void;

    /**
     * @param int $jobId
     */
    public function abandoned(int $jobId): void;

    /**
     * @param string $jobId
     */
    public function requeued(string $jobId): void;

    /**
     * @param string $jobId
     */
    public function rejected(string $jobId): void;

    /**
     * @param int $jobId
     */
    public function letGo(int $jobId): void;
}
