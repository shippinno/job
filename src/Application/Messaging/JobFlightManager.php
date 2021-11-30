<?php

namespace Shippinno\Job\Application\Messaging;

interface JobFlightManager
{
    /**
     * @param int $jobId
     * @param string $jobName
     * @param string $queue
     */
    public function created(int $jobId, string $jobName, string $queue): void;

    /**
     * @param int $jobId
     * @return mixed
     */
    public function boarding(int $jobId): void;

    /**
     * @param int $jobId
     */
    public function departed(int $jobId): void;

    /**
     * @param int $jobId
     */
    public function arrived(int $jobId): void;

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
     */
    public function requeued(string $jobId): void;

    /**
     * @param int $jobId
     */
    public function rejected(int $jobId): void;

    /**
     * @param int $jobId
     */
    public function letGo(int $jobId): void;

    /**
     * @param int $jobId
     * @return null|JobFlight
     */
    public function latestJobFlightOfJobId(int $jobId): ?JobFlight;

    /**
     * @param string $queue
     * @return int[]
     */
    public function undepartedJobFlights(string $queue): array;
}
