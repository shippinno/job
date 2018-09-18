<?php

namespace Shippinno\Job\Domain\Model;

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

    /**
     * @param int $jobId
     * @return null|StoredJob
     */
    public function storedJobOfId(int $jobId): ?StoredJob;
}
