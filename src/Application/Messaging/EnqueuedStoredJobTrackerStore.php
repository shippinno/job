<?php

namespace Shippinno\Job\Application\Messaging;

use Shippinno\Job\Domain\Model\StoredJob;

interface EnqueuedStoredJobTrackerStore
{
    /**
     * @param string $topic
     * @param StoredJob $storedJob
     */
    public function trackLastEnqueuedStoredJob(string $topic, StoredJob $storedJob): void;

    /**
     * @param string $topic
     * @return int|null
     */
    public function lastEnqueuedStoredJobId(string $topic): ?int;
}