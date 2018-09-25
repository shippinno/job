<?php

namespace Shippinno\Job\Application\Messaging;

class NullJobFlightManager implements JobFlightManager
{
    /**
     * {@inheritdoc}
     */
    public function departed(int $jobId, string $jobName, string $queue): void
    {

    }

    /**
     * {@inheritdoc}
     */
    public function latestJobFlightOfJobId(int $jobId): ?JobFlight
    {

    }

    /**
     * {@inheritdoc}
     */
    public function acknowledged(int $jobId): void
    {

    }

    /**
     * {@inheritdoc}
     */
    public function abandoned(int $jobId): void
    {

    }

    /**
     * {@inheritdoc}
     */
    public function requeued(string $jobId): void
    {

    }

    /**
     * {@inheritdoc}
     */
    public function rejected(int $jobId): void
    {

    }

    /**
     * {@inheritdoc}
     */
    public function letGo(int $jobId): void
    {

    }
}