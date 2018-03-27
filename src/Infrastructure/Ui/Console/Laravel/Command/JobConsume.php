<?php

namespace Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\ORM\EntityManager;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Application;
use Illuminate\Contracts\Container\Container;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use JMS\Serializer\Serializer;
use Shippinno\Job\Application\Job\Job;
use Shippinno\Job\Application\Job\JobRunner;
use Shippinno\Job\Domain\Model\JobFailedException;

class JobConsume extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $signature = 'job:consume';

    /**
     * @var PsrContext
     */
    private $context;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Container
     */
    private $container;

    /**
     * @param PsrContext $context
     * @param Serializer $serializer
     * @param EntityManager $entityManager
     * @param Container $container
     */
    public function __construct(
        PsrContext $context,
        Serializer $serializer,
        EntityManager $entityManager,
        Container $container
    ) {
        parent::__construct();
        $this->context = $context;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->container = $container;
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
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function consume(PsrConsumer $consumer)
    {
        $this->entityManager->clear();
        $message = $consumer->receive();
        $messageBody = json_decode($message->getBody());
        /** @var Job $job */
        $job = $this->serializer->deserialize($messageBody->body, $messageBody->name, 'json');
        if ($message->getProperty('attempts') > $job->maxAttempts()) {
            $consumer->reject($message);
        }
        /** @var JobRunner $jobRunner */
        $jobRunner = $this->container->make($job->jobRunner());
        try {
            $jobRunner->run($job);
            $consumer->acknowledge($message);
        } catch (JobFailedException $e) {
            $message->setProperty(
                'attempts',
                intval($message->getProperty('attempts', 0)) ?: 0
            );
            if ($job->reattemptDelay() > 0) {
                $message = $this->delayMessage($message, $job->reattemptDelay());
            }
            $consumer->reject($message, true);
        } finally {
            $this->entityManager->flush();
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
