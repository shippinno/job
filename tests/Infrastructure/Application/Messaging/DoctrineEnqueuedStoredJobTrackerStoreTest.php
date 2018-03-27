<?php

namespace Shippinno\Job\Infrastructure\Application\Messaging;

use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Mockery;
use PHPUnit\Framework\TestCase;
use Shippinno\Job\Application\Job\FakeJob;
use Shippinno\Job\Application\Job\StoredJob;
use Shippinno\Job\Application\Messaging\EnqueuedStoredJobTracker;
use Shippinno\Job\Application\Messaging\EnqueuedStoredJobTrackerStore;
use Shippinno\Job\Infrastructure\Application\Job\DoctrineJobStore;


class DoctrineEnqueuedStoredJobTrackerStoreTest extends TestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var EnqueuedStoredJobTrackerStore
     */
    private $enqueuedStoredJobTrackerStore;

    /**
     * @var DoctrineJobStore
     */
    private $jobStore;

    public function setUp()
    {
        $this->entityManager = $this->initEntityManager();
        $this->initSchema($this->entityManager);
        $this->enqueuedStoredJobTrackerStore = $this->createEnqueuedStoredJobTrackerStore();
        $this->jobStore = $this->createJobStore();
    }

    public function testLastEnqueuedStoredJobId()
    {
        $topic = 'TOPIC';
        $this->assertNull($this->enqueuedStoredJobTrackerStore->lastEnqueuedStoredJobId($topic));
        $storedJob1 = Mockery::mock(StoredJob::class);
        $storedJob1->shouldReceive(['id' => 12345]);
        $this->enqueuedStoredJobTrackerStore->trackLastEnqueuedStoredJob($topic, $storedJob1);
        $this->entityManager->flush();
        $this->assertSame(12345, $this->enqueuedStoredJobTrackerStore->lastEnqueuedStoredJobId($topic));
        $storedJob2 = Mockery::mock(StoredJob::class);
        $storedJob2->shouldReceive(['id' => 23456]);
        $this->enqueuedStoredJobTrackerStore->trackLastEnqueuedStoredJob($topic, $storedJob2);
        $this->entityManager->flush();
        $this->assertSame(23456, $this->enqueuedStoredJobTrackerStore->lastEnqueuedStoredJobId($topic));
    }

    protected function initEntityManager(): EntityManager
    {
        return EntityManager::create(
            ['url' => 'sqlite:///:memory:'],
            Setup::createXMLMetadataConfiguration(
                [__DIR__.'/../../Persistence/Doctrine/Mapping'],
                $devMode = true
            )
        );
    }

    private function initSchema(EntityManager $entityManager)
    {
        $tool = new SchemaTool($entityManager);
        $tool->createSchema([
            $entityManager->getClassMetadata(StoredJob::class),
            $entityManager->getClassMetadata(EnqueuedStoredJobTracker::class),
        ]);
    }

    private function createJobStore()
    {
        return new DoctrineJobStore(
            $this->entityManager,
            $this->entityManager->getClassMetaData(StoredJob::class)
        );
    }

    private function createEnqueuedStoredJobTrackerStore()
    {
        return new DoctrineEnqueuedStoredJobTrackerStore(
            $this->entityManager,
            $this->entityManager->getClassMetaData(EnqueuedStoredJobTracker::class)
        );
    }
}