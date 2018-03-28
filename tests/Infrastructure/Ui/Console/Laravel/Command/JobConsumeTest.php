<?php

namespace Shippinno\Job\Test\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use Illuminate\Container\Container;
use Interop\Queue\PsrConsumer;
use JMS\Serializer\SerializerBuilder;
use Mockery;
use ReflectionClass;
use Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command\JobConsume;
use Shippinno\Job\Test\Application\Job\FakeJobRunner;
use Shippinno\Job\Test\Domain\Model\FakeJob;
use Shippinno\Job\Domain\Model\StoredJob;
use Shippinno\Job\Infrastructure\Serialization\JMS\BuildsSerializer;
use Shippinno\Job\Test\TestCase;

class JobConsumeTest extends TestCase
{
    use BuildsSerializer;

    public function setUp()
    {
        $this->buildSerializer($this->serializerBuilder());
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

        $jobRunner = Mockery::mock(FakeJobRunner::class);
        $jobRunner
            ->shouldReceive('run')
//            ->withArgs()
            ->once();

        $jobConsume = new JobConsume(
            new NullContext,
            $this->serializerBuilder(),
            $this->initEntityManager(),
            $this->container([FakeJobRunner::class, $jobRunner])
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

        $jobRunner = Mockery::mock(FakeJobRunner::class);
        $jobRunner
            ->shouldReceive('run')
//            ->withArgs()
            ->once();

        $jobConsume = new JobConsume(
            new NullContext,
            $this->serializerBuilder(),
            $this->initEntityManager(),
            $this->container([FakeJobRunner::class, $jobRunner])
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
            $this->serializerBuilder(),
            $this->initEntityManager(),
            $this->container()
        );

        $reflection = new ReflectionClass($jobConsume);
        $method = $reflection->getMethod('consume');
        $method->setAccessible(true);
        $method->invokeArgs($jobConsume, [$consumer]);

        $this->assertTrue(true);
    }

    /**
     * @return EntityManager
     * @throws \Doctrine\ORM\ORMException
     */
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

    /**
     * @param array $bindings
     * @return Container
     */
    private function container(array $bindings = []): Container
    {
        $container = new Container();
        foreach ($bindings as $abstract => $concrete) {
            $container->bind($abstract, $concrete);
        }

        return $container;
    }

    /**
     * @param bool $failsToConsume
     * @return NullMessage
     */
    private function createMessage(bool $failsToConsume = false): NullMessage
    {
        $message = new NullMessage($this->serializer->serialize(
            new StoredJob(
                FakeJob::class,
                json_encode(['fails' => $failsToConsume]),
                new \DateTimeImmutable()
            ),
            'json'
        ));

        return $message;
    }

}
