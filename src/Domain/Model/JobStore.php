<?php

namespace Shippinno\Job\Domain\Model;

interface JobStore
{
    /**
     * @param Job $job
     * @return StoredJob
     */
    public function append(Job $job): StoredJob;

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

    /**
     * @param int[] $jobIds
     * @return StoredJob[]
     */
    public function storedJobsOfIds(array $jobIds): array;
}
