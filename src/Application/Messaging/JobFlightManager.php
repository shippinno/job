<?php

namespace Shippinno\Job\Application\Messaging;

interface JobFlightManager
{
    /**
     * @param int $jobId
     * @param string $queue
     */
    public function departed(int $jobId, string $queue): void;

    /**
     * @param int $jobId
     * @return null|JobFlight
     */
    public function latestJobFlightOfJobId(int $jobId): ?JobFlight;

    /**
     * @param int $jobId
     */
    public function acknowledged(int $jobId): void;

    /**
     * @param int $jobId
     */
    public function abandoned(int $jobId): void;

    /**
     * @param string $jobId
     * @param string $requeuedJobId
     */
    public function requeued(string $jobId, string $requeuedJobId): void;

    /**
     * @param int $jobId
     */
    public function rejected(int $jobId): void;

    /**
     * @param int $jobId
     */
    public function letGo(int $jobId): void;
}
