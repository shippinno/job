<?php

namespace Shippinno\Job\Infrastructure\Application\Messaging;

use Doctrine\ORM\EntityRepository;
use Shippinno\Job\Application\Messaging\JobFlight;
use Shippinno\Job\Application\Messaging\JobFlightManager;

class DoctrineJobFlightManager extends EntityRepository implements JobFlightManager
{
    /**
     * @param int $jobId
     * @param string $jobName
     * @param string $queue
     */
    public function created(int $jobId, string $jobName, string $queue): void
    {
        $this->getEntityManager()->persist(new JobFlight($jobId, $jobName, $queue));
        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function boarding(int $jobId): void
    {
        $this->latestJobFlightOfJobId($jobId)->board();
        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function departed(int $jobId): void
    {
        $this->latestJobFlightOfJobId($jobId)->depart();
        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function arrived(int $jobId): void
    {
        $this->latestJobFlightOfJobId($jobId)->arrive();
        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function acknowledged(int $jobId): void
    {
        $this->latestJobFlightOfJobId($jobId)->acknowledge();
        $this->getEntityManager()->flush();

    }

    /**
     * {@inheritdoc}
     */
    public function abandoned(int $jobId): void
    {
        $this->latestJobFlightOfJobId($jobId)->abandoned();
        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function requeued(string $jobId, string $requeuedJobId): void
    {
        $this->latestJobFlightOfJobId($jobId)->requeued();
        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function rejected(int $jobId): void
    {
        $this->latestJobFlightOfJobId($jobId)->rejected();
        $this->getEntityManager()->flush();

    }

    /**
     * {@inheritdoc}
     */
    public function letGo(int $jobId): void
    {
        $this->latestJobFlightOfJobId($jobId)->letGo();
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
    public function preBoardingJobFlights(string $queue): array
    {
        return array_column($this->createQueryBuilder('j')
            ->select('j.jobId')
            ->where('j.departure is null')
            ->andWhere('j.queue = :queue')
            ->setParameter('queue', $queue)
            ->orderBy('j.jobId')
            ->setMaxResults(100)
            ->getQuery()
            ->getArrayResult(), 'jobId');
    }
}
