<?php

namespace Shippinno\Job\Infrastructure\Application\Job;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Shippinno\Job\Application\Job\Job;
use Shippinno\Job\Application\Job\JobStore;
use Shippinno\Job\Application\Job\StoredJob;

class DoctrineJobStore extends EntityRepository implements JobStore
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * {@inheritdoc}
     */
    public function append(Job $job): void
    {
        $storedEvent = new StoredJob(
            get_class($job),
            $this->serializer()->serialize($job, 'json'),
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

    /**
     * @return Serializer
     */
    private function serializer(): Serializer
    {
        if (null === $this->serializer) {
            $this->serializer =
                SerializerBuilder::create()
                    ->addMetadataDir(__DIR__.'/../../Serialization/JMS/Config')
                    ->setCacheDir(__DIR__ . '/../../../../var/cache/jms-serializer')
                    ->build();
        }

        return $this->serializer;
    }
}
