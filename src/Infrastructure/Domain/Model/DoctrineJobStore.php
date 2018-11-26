<?php

namespace Shippinno\Job\Infrastructure\Domain\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
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
     * @param EntityManager $em
     * @param ClassMetadata $class
     * @param JobSerializer $jobSerializer
     */
    public function __construct(EntityManager $em, ClassMetadata $class, JobSerializer $jobSerializer)
    {
        parent::__construct($em, $class);
        $this->jobSerializer = $jobSerializer;
    }

    /**
     * {@inheritdoc}
     */
    public function append(Job $job): void
    {
        $storedJob = new StoredJob(
            get_class($job),
            $this->jobSerializer->serialize($job),
            $job->createdAt(),
            $job->isExpendable(),
            $job->fifoGroupId(),
            $job->deduplicationId()
        );
        $this->getEntityManager()->persist($storedJob);
    }

    /**
     * {@inheritdoc}
     */
    public function storedJobsSince(?int $jobId): array
    {
        $query = $this->createQueryBuilder('j');
        $query->where('j.createdAt < :createdBefore');
        $query->setParameter('createdBefore', new \DateTimeImmutable('-1 minute'));
        if (null !== $jobId) {
            $query->andWhere('j.id > :id');
            $query->setParameter('id', $jobId);
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
