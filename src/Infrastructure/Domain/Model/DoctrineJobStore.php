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
            $job->createdAt()
        );
        $this->getEntityManager()->persist($storedJob);
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
        $query->orderBy('j.id');

        return $query->getQuery()->getResult();
    }
}
