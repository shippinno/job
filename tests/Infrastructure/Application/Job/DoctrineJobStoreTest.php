<?php

namespace Shippinno\Job\Application\Job;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use PHPUnit\Framework\TestCase;
use Shippinno\Job\Infrastructure\Application\Job\DoctrineJobStore;

class DoctrineJobStoreTest extends TestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var DoctrineJobStore
     */
    private $jobStore;

    public function setUp()
    {
        $this->entityManager = $this->initEntityManager();
        $this->jobStore = new DoctrineJobStore(
            $this->entityManager,
            $this->entityManager->getClassMetaData(StoredJob::class)
        );
    }

    public function testStoredJobsSinceWithJobIdNull()
    {
        $this->jobStore->append(new FakeJob);
        $this->entityManager->flush();
        $storedEvents = $this->jobStore->storedJobsSince(null);
        $this->assertCount(1, $storedEvents);
        $this->assertSame(1, $storedEvents[0]->id());
    }

    public function testStoredJobsSinceWithJobId()
    {
        $this->jobStore->append(new FakeJob);
        $this->jobStore->append(new FakeJob);
        $this->jobStore->append(new FakeJob);
        $this->entityManager->flush();
        $storedEvents = $this->jobStore->storedJobsSince(1);
        $this->assertCount(2, $storedEvents);
        $this->assertSame(2, $storedEvents[0]->id());
        $this->assertSame(3, $storedEvents[1]->id());
    }

    protected function initEntityManager(): EntityManager
    {
        $entityManager = EntityManager::create(
            ['url' => 'sqlite:///:memory:'],
            Setup::createXMLMetadataConfiguration(
                [__DIR__ . '/../../Persistence/Doctrine/Mapping'],
                $devMode = true
            )
        );
        $tool = new SchemaTool($entityManager);
        $tool->createSchema([
            $entityManager->getClassMetadata(StoredJob::class),
        ]);

        return $entityManager;
    }
}