<?php

namespace Shippinno\Job\Application\Messaging;

use Closure;
use Enqueue\Sqs\SqsMessage;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shippinno\Job\Application\Job\JobRunnerRegistry;
use Shippinno\Job\Domain\Model\AbandonedJobMessage;
use Shippinno\Job\Domain\Model\AbandonedJobMessageStore;
use Shippinno\Job\Domain\Model\JobFailedException;
use Shippinno\Job\Domain\Model\JobRunnerNotRegisteredException;
use Shippinno\Job\Domain\Model\JobSerializer;
use Shippinno\Job\Domain\Model\JobStore;
use Shippinno\Job\Domain\Model\StoredJobSerializer;

class ConsumeStoredJobService
{
    use LoggerAwareTrait;

    /**
     * @var StoredJobSerializer
     */
    private $storedJobSerializer;

    /**
     * @var PsrContext
     */
    private $context;

    /**
     * @var JobSerializer
     */
    private $jobSerializer;

    /**
     * @var JobRunnerRegistry
     */
    private $jobRunnerRegistry;

    /**
     * @var JobStore
     */
    private $jobStore;

    /**
     * @var AbandonedJobMessageStore
     */
    private $abandonedJobMessageStore;

    /**
     * @param PsrContext $context
     * @param StoredJobSerializer $storedJobSerializer
     * @param JobSerializer $jobSerializer
     * @param JobRunnerRegistry $jobRunnerRegistry
     * @param JobStore $jobStore
     * @param AbandonedJobMessageStore $abandonedJobMessageStore
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        PsrContext $context,
        StoredJobSerializer $storedJobSerializer,
        JobSerializer $jobSerializer,
        JobRunnerRegistry $jobRunnerRegistry,
        JobStore $jobStore,
        AbandonedJobMessageStore $abandonedJobMessageStore,
        LoggerInterface $logger = null
    ) {
        $this->context = $context;
        $this->storedJobSerializer = $storedJobSerializer;
        $this->jobSerializer = $jobSerializer;
        $this->jobRunnerRegistry = $jobRunnerRegistry;
        $this->jobStore = $jobStore;
        $this->abandonedJobMessageStore = $abandonedJobMessageStore;
        $this->setLogger(null !== $logger ? $logger : new NullLogger);
    }

    /**
     * @param string $queueName
     * @param Closure|null $persist
     */
    public function execute(string $queueName, Closure $persist = null): void
    {
        $consumer = $this->context->createConsumer($this->context->createQueue($queueName));
        $message = $consumer->receive(5000);
        if (null === $message) {
            return;
        }
        $storedJob = $this->storedJobSerializer->deserialize($message->getBody());
        $job = $this->jobSerializer->deserialize($storedJob->body(), $storedJob->name());
        try {
            $jobRunner = $this->jobRunnerRegistry->get(get_class($job));
        } catch (JobRunnerNotRegisteredException $e) {
            $this->abandonedJobMessageStore->add(
                new AbandonedJobMessage($queueName, $message->getBody(), $e->__toString())
            );
            $this->logger->alert(
                'No JobRunner is registered. Message is abandoned. Rejecting the message.',
                ['message' => $message->getBody()]
            );
            $consumer->reject($message);
            return;
        }
        try {
            $jobRunner->run($job);
            if (!is_null($persist) && !$persist()) {
                $this->logger->info(
                    'Persistence failed after the job. Requeueing the message.',
                    ['message' => $message->getBody()]
                );
                $consumer->reject($message, true);
                return;
            }
            $dependentJobs = $job->dependentJobs();
            if (count($dependentJobs) > 0) {
                foreach ($dependentJobs as $dependentJob) {
                    $this->jobStore->append($dependentJob);
                }
            }
            $this->logger->info('Acknowledging message.', ['message' => $message->getBody()]);
            $consumer->acknowledge($message);
        } catch (JobFailedException $e) {
            $attempts = $message->getProperty('attempts', 0) + 1;
            if ($attempts >= $job->maxAttempts()) {
                $this->abandonedJobMessageStore->add(
                    new AbandonedJobMessage($queueName, $message->getBody(), $e->__toString())
                );
                $this->logger->info(
                    'Rejecting the message reaching the max attempts.',
                    ['message' => $message->getBody()]
                );
                $consumer->reject($message);
                return;
            }
            $message->setProperty('attempts', $attempts);
            if ($job->reattemptDelay() > 0) {
                $message = $this->delayMessage($message, $job->reattemptDelay());
            }
            if (method_exists($message, 'setMessageDeduplicationId')) {
                $message->setMessageDeduplicationId(uniqid());
            }
            if (method_exists($message, 'setMessageGroupId')) {
                $message->setMessageGroupId(is_null($storedJob->fifoGroupId()) ? uniqid() : $storedJob->fifoGroupId());
            }
            $this->logger->info('Requeueing the message.', ['message' => $message->getBody()]);
            $consumer->reject($message, true);
        }
    }

    /**
     * @param PsrMessage $message
     * @param int $delay
     * @return PsrMessage
     */
    protected function delayMessage(PsrMessage $message, int $delay)
    {
        if ($message instanceof SqsMessage) {
            $message->setDelaySeconds($delay);
        }

        return $message;
    }
}
