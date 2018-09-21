<?php

namespace Shippinno\Job\Test\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use JMS\Serializer\SerializerBuilder;
use Mockery;
use PHPUnit\Framework\TestCase;
use Shippinno\Job\Domain\Model\JobSerializer;
use Shippinno\Job\Domain\Model\StoredJob;
use Shippinno\Job\Infrastructure\Domain\Model\DoctrineJobStore;

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
        $jobSerializer = Mockery::mock(JobSerializer::class);
        $jobSerializer->shouldReceive(['serialize' => '']);
        $this->jobStore = new DoctrineJobStore(
            $this->entityManager,
            $this->entityManager->getClassMetaData(StoredJob::class),
            $jobSerializer
        );
    }

    public function testStoredJobsSinceWithJobIdNull()
    {
        $this->jobStore->append(new FakeJob(false, new DateTimeImmutable('-2 minutes')));
        $this->entityManager->flush();
        $storedJobs = $this->jobStore->storedJobsSince(null);
        $this->assertCount(1, $storedJobs);
        $this->assertSame(1, $storedJobs[0]->id());
    }

    public function testStoredJobsSinceWithJobId()
    {
        $this->jobStore->append(new FakeJob(false, new DateTimeImmutable('-3 minutes')));
        $this->jobStore->append(new FakeJob(false, new DateTimeImmutable('-2 minutes')));
        $this->jobStore->append(new FakeJob(false, new DateTimeImmutable('-1 minutes')));
        $this->entityManager->flush();
        $storedJobs = $this->jobStore->storedJobsSince(1);
        $this->assertCount(1, $storedJobs);
        $this->assertSame(2, $storedJobs[0]->id());
    }

    protected function initEntityManager(): EntityManager
    {
        $entityManager = EntityManager::create(
            ['url' => 'sqlite:///:memory:'],
            Setup::createXMLMetadataConfiguration(
                [__DIR__ . '/../../Infrastructure/Persistence/Doctrine/Mapping'],
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