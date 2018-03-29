<?php

namespace Shippinno\Job\Test\Domain\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use PHPUnit\Framework\TestCase;
use Shippinno\Job\Domain\Model\AbandonedJobMessage;
use Shippinno\Job\Domain\Model\StoredJob;
use Shippinno\Job\Infrastructure\Domain\Model\DoctrineAbandonedJobMessageStore;

class DoctrineAbandonedJobMessageStoreTest extends TestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var DoctrineAbandonedJobMessageStore
     */
    private $abandonedJobMessageStore;

    public function setUp()
    {
        $this->entityManager = $this->initEntityManager();
        $this->abandonedJobMessageStore = new DoctrineAbandonedJobMessageStore(
            $this->entityManager,
            $this->entityManager->getClassMetaData(AbandonedJobMessage::class)
        );
    }

    public function testAddRemove()
    {
        $abandonedJobMessage = new AbandonedJobMessage('QUEUE', 'MESSAGE', 'REASON');
        $this->abandonedJobMessageStore->add($abandonedJobMessage);
        $this->entityManager->flush();
        $abandonedJobMessages = $this->abandonedJobMessageStore->all();
        $this->assertCount(1, $abandonedJobMessages);
        $this->assertSame(1, $abandonedJobMessages[0]->id());
        $this->assertSame($abandonedJobMessage->queue(), $abandonedJobMessages[0]->queue());
        $this->assertSame($abandonedJobMessage->name(), $abandonedJobMessages[0]->name());
        $this->assertSame($abandonedJobMessage->reason(), $abandonedJobMessages[0]->reason());
        $this->assertSame($abandonedJobMessage->abandonedAt(), $abandonedJobMessages[0]->abandonedAt());
        $this->abandonedJobMessageStore->remove($abandonedJobMessages[0]);
        $this->entityManager->flush();
        $this->assertCount(0, $this->abandonedJobMessageStore->all());
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
