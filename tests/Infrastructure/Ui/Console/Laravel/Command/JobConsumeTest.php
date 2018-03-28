<?php

namespace Shippinno\Job\Test\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use Interop\Queue\PsrConsumer;
use Mockery;
use ReflectionClass;
use Shippinno\Job\Application\Job\JobRunnerRegistry;
use Shippinno\Job\Domain\Model\JobSerializer;
use Shippinno\Job\Domain\Model\StoredJobSerializer;
use Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command\JobConsume;
use Shippinno\Job\Test\Application\Job\FakeJobRunner;
use Shippinno\Job\Test\Domain\Model\FakeJob;
use Shippinno\Job\Domain\Model\StoredJob;
use Shippinno\Job\Test\Domain\Model\FakeJobSerializer;
use Shippinno\Job\Test\Domain\Model\FakeStoredJobSerializer;
use Shippinno\Job\Test\TestCase;

class JobConsumeTest extends TestCase
{
    /**
     * @var JobSerializer
     */
    private $jobSerializer;

    /**
     * @var StoredJobSerializer
     */
    private $storedJobSerializer;

    public function setUp()
    {
        $this->jobSerializer = new FakeJobSerializer;
        $this->storedJobSerializer = new FakeStoredJobSerializer;
    }

    public function testItShouldAcknowledgeWhenSucceeded()
    {
        $message = $this->createMessage();

        $consumer = Mockery::mock(PsrConsumer::class);
        $consumer
            ->shouldReceive('receive')->andReturn($message)->once()
            ->shouldReceive('acknowledge')
//            ->withArgs([
//                Mockery::on(function (NullMessage $message) use ($messageBodyToSucceed) {
//                    return $message->getBody() === $messageBodyToSucceed;
//                })
//            ])
            ->once();

        $jobConsume = new JobConsume(
            new NullContext,
            $this->jobSerializer,
            $this->storedJobSerializer,
            $this->jobRunnerRegistry([FakeJob::class => new FakeJobRunner]),
            $this->mockManagerRegistry()
        );

        $reflection = new ReflectionClass($jobConsume);
        $method = $reflection->getMethod('consume');
        $method->setAccessible(true);
        $method->invokeArgs($jobConsume, [$consumer]);

        $this->assertTrue(true);
    }

    public function testItShouldRejectWhenFailed()
    {
        $message = $this->createMessage(true);

        $consumer = Mockery::mock(PsrConsumer::class);
        $consumer
            ->shouldReceive('receive')->andReturn($message)->once()
            ->shouldReceive('reject')
//            ->withArgs([
//                Mockery::on(function (NullMessage $message) use ($messageBodyToSucceed) {
//                    return $message->getBody() === $messageBodyToSucceed;
//                })
//            ])
            ->once();
        
        $jobConsume = new JobConsume(
            new NullContext,
            $this->jobSerializer,
            $this->storedJobSerializer,
            $this->jobRunnerRegistry([FakeJob::class => new FakeJobRunner]),
            $this->mockManagerRegistry()
        );

        $reflection = new ReflectionClass($jobConsume);
        $method = $reflection->getMethod('consume');
        $method->setAccessible(true);
        $method->invokeArgs($jobConsume, [$consumer]);

        $this->assertTrue(true);
    }

    public function testItRejectsWhenExceedsMaxAttempts()
    {
        $message = $this->createMessage(true);
        $message->setProperty('attempts', 3);

        $consumer = Mockery::mock(PsrConsumer::class);
        $consumer
            ->shouldReceive('receive')->andReturn($message)->once()
            ->shouldReceive('reject')
//            ->withArgs([
//                Mockery::on(function (NullMessage $message) use ($messageBodyToSucceed) {
//                    return $message->getBody() === $messageBodyToSucceed;
//                })
//            ])
            ->once();

        $jobConsume = new JobConsume(
            new NullContext,
            $this->jobSerializer,
            $this->storedJobSerializer,
            $this->jobRunnerRegistry(),
            $this->mockManagerRegistry()
        );

        $reflection = new ReflectionClass($jobConsume);
        $method = $reflection->getMethod('consume');
        $method->setAccessible(true);
        $method->invokeArgs($jobConsume, [$consumer]);

        $this->assertTrue(true);
    }

    protected function initEntityManager()
    {
        return EntityManager::create(
            ['url' => 'sqlite:///:memory:'],
            Setup::createXMLMetadataConfiguration(
                [__DIR__.'/../../Persistence/Doctrine/Mapping'],
                $devMode = true
            )
        );
    }

    protected function mockManagerRegistry()
    {
        $managerRegistry = Mockery::mock(ManagerRegistry::class);
        $managerRegistry->shouldReceive(['getManager' => $this->initEntityManager()]);

        return $managerRegistry;
    }

    /**
     * @param bool $failsToConsume
     * @return NullMessage
     */
    private function createMessage(bool $failsToConsume = false): NullMessage
    {
        $message = new NullMessage($this->storedJobSerializer->serialize(
            new StoredJob(
                FakeJob::class,
                $this->jobSerializer->serialize(new FakeJob($failsToConsume)),
                new \DateTimeImmutable()
            )
        ));

        return $message;
    }

    private function jobRunnerRegistry(array $jobRunners = []): JobRunnerRegistry
    {
        $jobRunnerRegistry = new JobRunnerRegistry();
        $jobRunnerRegistry->register($jobRunners);

        return $jobRunnerRegistry;
    }
}
