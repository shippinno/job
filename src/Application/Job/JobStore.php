<?php

namespace Shippinno\Job\Application\Job;

interface JobStore
{
    /**
     * @param Job $job
     */
    public function append(Job $job): void;

    /**
     * @param int|null $jobId
     * @return StoredJob[]
     */
    public function storedJobsSince(?int $jobId): array;
}
