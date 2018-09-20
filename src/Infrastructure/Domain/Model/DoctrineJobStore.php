<?php

namespace Shippinno\Job\Infrastructure\Domain\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Shippinno\Job\Application\Messaging\JobFlightManager;
use Shippinno\Job\Domain\Model\Job;
use Shippinno\Job\Domain\Model\JobSerializer;
use Shippinno\Job\Domain\Model\JobStore;
use Shippinno\Job\Domain\Model\StoredJob;

class DoctrineJobStore extends EntityRepository implements JobStore
{
    /**
     * @var JobSerializer
     */
    private $jobSerializer;

    /**
     * @var JobFlightManager
     */
    private $jobFlightManager;

    /**
     * @param EntityManager $em
     * @param ClassMetadata $class
     * @param JobSerializer $jobSerializer
     */
    public function __construct(
        EntityManager $em,
        ClassMetadata $class,
        JobSerializer $jobSerializer,
        JobFlightManager $jobFlightManager
    ) {
        parent::__construct($em, $class);
        $this->jobSerializer = $jobSerializer;
        $this->jobFlightManager = $jobFlightManager;
    }

    /**
     * {@inheritdoc}
     */
    public function append(Job $job): StoredJob
    {
        $storedJob = new StoredJob(
            get_class($job),
            $this->jobSerializer->serialize($job),
            $job->createdAt(),
            $job->isExpendable(),
            $job->fifoGroupId()
        );
        $this->getEntityManager()->persist($storedJob);

        $this->jobFlightManager->created($storedJob->id(), $storedJob->name(), env('JOB_ENQUEUE_TOPIC'));

        return $storedJob;
    }

    /**
     * {@inheritdoc}
     */
    public function storedJobsSince(?int $jobId): array
    {
        $query = $this->createQueryBuilder('j');
        if (null !== $jobId) {
            $query->where('j.id > :id');
            $query->setParameters(['id' => $jobId]);
        }
        $query->setMaxResults(100);
        $query->orderBy('j.id');

        return $query->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function storedJobOfId(int $jobId): ?StoredJob
    {
        /** @var StoredJob|null $storedJob */
        $storedJob = $this->find($jobId);

        return $storedJob;
    }
}
