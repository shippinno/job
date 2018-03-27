<?php

namespace Shippinno\Job\Application\Messaging;

class EnqueuedStoredJobTracker
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $topic;

    /**
     * @var int
     */
    private $lastEnqueuedStoredJobId;

    /**
     * @param string $topic
     * @param int $lastEnqueuedStoredJobId
     */
    public function __construct(string $topic, int $lastEnqueuedStoredJobId)
    {
        $this->topic = $topic;
        $this->lastEnqueuedStoredJobId = $lastEnqueuedStoredJobId;
    }

    /**
     * @return int
     */
    public function lastEnqueuedStoredJobId(): int
    {
        return $this->lastEnqueuedStoredJobId;
    }

    /**
     * @param int $lastEnqueuedStoredJobId
     */
    public function updateLastEnqueuedStoredJobId(int $lastEnqueuedStoredJobId): void
    {
        $this->lastEnqueuedStoredJobId = $lastEnqueuedStoredJobId;
    }
}
