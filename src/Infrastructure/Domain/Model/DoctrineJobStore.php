<?php

namespace Shippinno\Job\Infrastructure\Domain\Model;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use JMS\Serializer\SerializerBuilder;
use Shippinno\Job\Domain\Model\Job;
use Shippinno\Job\Domain\Model\JobStore;
use Shippinno\Job\Domain\Model\StoredJob;
use Shippinno\Job\Infrastructure\Serialization\JMS\BuildsSerializer;

class DoctrineJobStore extends EntityRepository implements JobStore
{
    use BuildsSerializer;

    /**
     * @param $em
     * @param ClassMetadata $class
     * @param SerializerBuilder $serializerBuilder
     */
    public function __construct($em, ClassMetadata $class, SerializerBuilder $serializerBuilder)
    {
        parent::__construct($em, $class);
        $this->buildSerializer($serializerBuilder);
    }

    /**
     * {@inheritdoc}
     */
    public function append(Job $job): void
    {
        $storedEvent = new StoredJob(
            get_class($job),
            $this->serializer->serialize($job, 'json'),
            $job->createdAt()
        );
        $this->getEntityManager()->persist($storedEvent);
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
