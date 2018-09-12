<?php

namespace Shippinno\Job\Infrastructure\Application\Messaging;

use Doctrine\ORM\EntityRepository;
use Shippinno\Job\Application\Messaging\JobFlight;
use Shippinno\Job\Application\Messaging\JobFlightManager;

class DoctrineJobFlightManager extends EntityRepository implements JobFlightManager
{
    /**
     * {@inheritdoc}
     */
    public function departed(int $jobId, string $queue): void
    {
        $this->getEntityManager()->persist(new JobFlight($jobId, $queue));
        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function latestJobFlightOfJobId(int $jobId): ?JobFlight
    {
        return $this->findOneBy(['jobId' => $jobId], ['id' => 'DESC']);
    }

    /**
     * {@inheritdoc}
     */
    public function acknowledged(int $jobId): void
    {
        $jobFlight = $this->latestJobFlightOfJobId($jobId);
        if (!is_null($jobFlight)) {
            $jobFlight->acknowledge();
            $this->getEntityManager()->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function abandoned(int $jobId): void
    {
        $jobFlight = $this->latestJobFlightOfJobId($jobId);
        if (!is_null($jobFlight)) {
            $jobFlight->abandoned();
            $this->getEntityManager()->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function requeued(int $jobId): void
    {
        $jobFlight = $this->latestJobFlightOfJobId($jobId);
        if (!is_null($jobFlight)) {
            $jobFlight->requeued();
            $this->departed($jobFlight->jobId(), $jobFlight->queue());
            $this->getEntityManager()->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rejected(int $jobId): void
    {
        $jobFlight = $this->latestJobFlightOfJobId($jobId);
        if (!is_null($jobFlight)) {
            $this->latestJobFlightOfJobId($jobId)->rejected();
            $this->getEntityManager()->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function letGo(int $jobId): void
    {
        $jobFlight = $this->latestJobFlightOfJobId($jobId);
        if (!is_null($jobFlight)) {
            $jobFlight->letGo();
            $this->getEntityManager()->flush();
        }
    }
}
