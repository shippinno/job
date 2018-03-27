<?php

namespace Shippinno\Job\Infrastructure\Application\Messaging;

use Doctrine\ORM\EntityRepository;
use Shippinno\Job\Application\Job\StoredJob;
use Shippinno\Job\Application\Messaging\EnqueuedStoredJobTracker;
use Shippinno\Job\Application\Messaging\EnqueuedStoredJobTrackerStore;

class DoctrineEnqueuedStoredJobTrackerStore extends EntityRepository implements EnqueuedStoredJobTrackerStore
{
    /**
     * {@inheritdoc}
     * @throws \Doctrine\ORM\ORMException
     */
    public function trackLastEnqueuedStoredJob(string $topic, StoredJob $storedJob): void
    {
        $id = $storedJob->id();
        $enqueuedStoredJobTracker = $this->findOneByTopic($topic);
        if (null === $enqueuedStoredJobTracker) {
            $enqueuedStoredJobTracker = new EnqueuedStoredJobTracker($topic, $id);
        } else {
            $enqueuedStoredJobTracker->updateLastEnqueuedStoredJobId($id);
        }
        $this->getEntityManager()->persist($enqueuedStoredJobTracker);
    }

    /**
     * @param string $topic
     * @return int|null
     */
    public function lastEnqueuedStoredJobId(string $topic): ?int
    {
        /** @var EnqueuedStoredJobTracker $enqueuedStoredJobTracker */
        $enqueuedStoredJobTracker = $this->findOneByTopic($topic);
        if (null === $enqueuedStoredJobTracker) {
            return null;
        }

        return $enqueuedStoredJobTracker->lastEnqueuedStoredJobId();
    }
}
