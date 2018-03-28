<?php

namespace Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use JMS\Serializer\SerializerBuilder;
use Shippinno\Job\Application\Job\JobRunner;
use Shippinno\Job\Domain\Model\Job;
use Shippinno\Job\Domain\Model\JobFailedException;
use Shippinno\Job\Infrastructure\Serialization\JMS\BuildsSerializer;

class JobConsume extends Command
{
    use BuildsSerializer;

    /**
     * {@inheritdoc}
     */
    protected $signature = 'job:consume';

    /**
     * @var PsrContext
     */
    private $context;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var ManagerRegistry|null
     */
    private $managerRegistry;

    /**
     * @param PsrContext $context
     * @param SerializerBuilder $serializerBuilder
     * @param Container $container
     * @param ManagerRegistry|null $managerRegistry
     */
    public function __construct(
        PsrContext $context,
        SerializerBuilder $serializerBuilder,
        Container $container,
        ManagerRegistry $managerRegistry = null
    ) {
        parent::__construct();
        $this->context = $context;
        $this->buildSerializer($serializerBuilder);
        $this->container = $container;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function handle()
    {
        $consumer = $this->createConsumer();
        while (true) {
            $this->consume($consumer);
        }
    }

    /**
     * @return PsrConsumer
     */
    protected function createConsumer(): PsrConsumer
    {
        $queue = $this->context->createQueue('test');
        $consumer = $this->context->createConsumer($queue);

        return $consumer;
    }

    /**
     * @param PsrConsumer $consumer
     */
    protected function consume(PsrConsumer $consumer)
    {
        if (null !== $this->managerRegistry) {
            $this->managerRegistry->getManager()->clear();
        }
        $message = $consumer->receive();
        if (null === $message) {
            return;
        }
        $messageBody = json_decode($message->getBody());
        /** @var Job $job */
        $job = $this->serializer->deserialize($messageBody->body, $messageBody->name, 'json');
        $attempts = $message->getProperty('attempts', 0) + 1;
        if ($attempts > $job->maxAttempts()) {
            $consumer->reject($message);
            return;
        }
        /** @var JobRunner $jobRunner */
        $jobRunner = $this->container->make($job->jobRunner());
        try {
            $jobRunner->run($job);
            $consumer->acknowledge($message);
        } catch (JobFailedException $e) {
            $message->setProperty('attempts', $attempts);
            if ($job->reattemptDelay() > 0) {
                $message = $this->delayMessage($message, $job->reattemptDelay());
            }
            $consumer->reject($message, true);
        } finally {
            if (null !== $this->managerRegistry) {
                $this->managerRegistry->getManager()->flush();
            }
        }
    }

    /**
     * @param PsrMessage $message
     * @param int $delay
     * @return PsrMessage
     */
    protected function delayMessage(PsrMessage $message, int $delay)
    {
        if (method_exists($message, 'setDelaySeconds')) {
            $message->setDelaySeconds($delay);
        }

        return $message;
    }
}
