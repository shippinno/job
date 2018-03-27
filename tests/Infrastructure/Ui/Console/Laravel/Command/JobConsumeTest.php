<?php

namespace Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use Illuminate\Container\Container;
use Interop\Queue\PsrConsumer;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Shippinno\Job\Application\Job\FakeJob;
use Shippinno\Job\Application\Job\FakeJobRunner;
use Shippinno\Job\Application\Job\StoredJob;

class JobConsumeTest extends TestCase
{
    /**
     * @var Serializer
     */
    private $serializer;

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
            $this->serializer(),
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
            $this->serializer(),
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
            $this->serializer(),
            $this->initEntityManager(),
            $this->container()
        );

        $reflection = new ReflectionClass($jobConsume);
        $method = $reflection->getMethod('consume');
        $method->setAccessible(true);
        $method->invokeArgs($jobConsume, [$consumer]);

        $this->assertTrue(true);
    }
//
//    public function test()
//    {
//        $messageBodyToSucceed = $this->serializer()->serialize(
//            new StoredJob(
//                FakeJob::class,
//                '{"fails":false}',
//                new \DateTimeImmutable()
//            ),
//            'json'
//        );
//        $messageBodyToFail = $this->serializer()->serialize(
//            new StoredJob(
//                FakeJob::class,
//                '{"fails":true}',
//                new \DateTimeImmutable()
//            ),
//            'json'
//        );
//        $messageToSucceed = new NullMessage($messageBodyToSucceed);
//        $messageToFail = new NullMessage($messageBodyToFail);
//
//        $consumer = Mockery::mock(PsrConsumer::class);
//        $consumer
//            ->shouldReceive('receive')
//            ->andReturn($messageToSucceed, $messageToFail)
//            ->shouldReceive('acknowledge')
////            ->withArgs([
////                Mockery::on(function (NullMessage $message) use ($messageBodyToSucceed) {
////                    return $message->getBody() === $messageBodyToSucceed;
////                })
////            ])
//            ->once()
//            ->shouldReceive('reject')
////            ->withArgs([
////                Mockery::on(function (NullMessage $message) use ($messageBodyToFail) {
////                    return $message->getBody() === $messageBodyToFail && 1 === $message->getProperty('attempts');
////                }),
////                true
////            ])
//            ->once();
//
//        $context = Mockery::mock(PsrContext::class);
//        $context->shouldReceive([
//            'createQueue' => new NullQueue('test'),
//            'createConsumer' => $consumer,
//        ]);
//
//        $jobRunner = Mockery::mock(FakeJobRunner::class);
//        $jobRunner
//            ->shouldReceive('run')
////            ->withArgs()
//            ->twice();
//
//        $jobConsume = new JobConsume(
//            $context,
//            $this->serializer(),
//            $this->initEntityManager(),
//            $this->container([FakeJobRunner::class, $jobRunner])
//        );
//
//        $reflection = new ReflectionClass($jobConsume);
//        $method = $reflection->getMethod('consume');
//        $method->setAccessible(true);
//        $method->invokeArgs($jobConsume, [$consumer]);
//        $method->invokeArgs($jobConsume, [$consumer]);
//
//        $this->assertTrue(true);
//    }

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
     * @return Serializer
     */
    private function serializer(): Serializer
    {
        if (null === $this->serializer) {
            $this->serializer =
                SerializerBuilder::create()
                    ->addMetadataDir(__DIR__.'/../../../../../Infrastructure/Serialization/JMS/Config')
                    ->build();
        }

        return $this->serializer;
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
        $message = new NullMessage($this->serializer()->serialize(
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
