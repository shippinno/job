<?php

namespace Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Illuminate\Console\Command;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shippinno\Job\Application\Job\JobRunnerRegistry;
use Shippinno\Job\Domain\Model\Job;
use Shippinno\Job\Domain\Model\JobFailedException;
use Shippinno\Job\Domain\Model\JobSerializer;
use Shippinno\Job\Domain\Model\StoredJobSerializer;

class JobConsume extends Command
{
    use LoggerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected $signature = 'job:consume';

    /**
     * @var PsrContext
     */
    private $context;

    /**
     * @var JobSerializer
     */
    private $jobSerializer;

    /**
     * @var StoredJobSerializer
     */
    private $storedJobSerializer;

    /**
     * @var JobRunnerRegistry
     */
    private $jobRunnerRegistry;

    /**
     * @var ManagerRegistry|null
     */
    private $managerRegistry;

    /**
     * @param PsrContext $context
     * @param JobSerializer $jobSerializer
     * @param StoredJobSerializer $storedJobSerializer
     * @param JobRunnerRegistry $jobRunnerRegistry
     * @param ManagerRegistry|null $managerRegistry
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        PsrContext $context,
        JobSerializer $jobSerializer,
        StoredJobSerializer $storedJobSerializer,
        JobRunnerRegistry $jobRunnerRegistry,
        ManagerRegistry $managerRegistry = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct();
        $this->context = $context;
        $this->jobSerializer = $jobSerializer;
        $this->storedJobSerializer = $storedJobSerializer;
        $this->jobRunnerRegistry = $jobRunnerRegistry;
        $this->managerRegistry = $managerRegistry;
        $this->setLogger(null !== $logger ? $logger : new NullLogger);
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
        $storedJob = $this->storedJobSerializer->deserialize($message->getBody());
        /** @var Job $job */
        $job = $this->jobSerializer->deserialize($storedJob->body(), $storedJob->name());
        $attempts = $message->getProperty('attempts', 0) + 1;
        if ($attempts > $job->maxAttempts()) {
            $consumer->reject($message);
//            $this->info(sprintf('Job exceeded max attempts (%d), rejected', $job->maxAttempts()));
            return;
        }
        $jobRunner =$this->jobRunnerRegistry->get(get_class($job));
        try {
            $jobRunner->run($job);
            $consumer->acknowledge($message);
//            $this->info('Job consumed successfuly, acknowledged');
        } catch (JobFailedException $e) {
            $message->setProperty('attempts', $attempts);
            if ($job->reattemptDelay() > 0) {
                $message = $this->delayMessage($message, $job->reattemptDelay());
            }
            $consumer->reject($message, true);
//            $this->info(sprintf('Job failed, requeued in %d seconds', $job->reattemptDelay()));
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
