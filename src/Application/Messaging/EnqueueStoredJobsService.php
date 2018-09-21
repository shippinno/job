<?php

namespace Shippinno\Job\Application\Messaging;

use Enqueue\Sqs\SqsMessage;
use Enqueue\Sqs\SqsProducer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrTopic;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shippinno\Job\Domain\Model\StoredJob;
use Shippinno\Job\Domain\Model\JobStore;
use Shippinno\Job\Domain\Model\FailedToEnqueueStoredJobException;
use Shippinno\Job\Domain\Model\StoredJobSerializer;
use Throwable;

class EnqueueStoredJobsService
{
    use LoggerAwareTrait;

    /**
     * @var PsrContext
     */
    private $context;

    /**
     * @var JobStore
     */
    protected $jobStore;

    /**
     * @var StoredJobSerializer
     */
    private $storedJobSerializer;

    /**
     * @var EnqueuedStoredJobTrackerStore
     */
    protected $enqueuedStoredJobTrackerStore;

    /**
     * @var JobFlightManager
     */
    private $jobFlightManager;

    /**
     * @param PsrContext $context
     * @param JobStore $jobStore
     * @param StoredJobSerializer $storedJobSerializer
     * @param EnqueuedStoredJobTrackerStore $enqueuedStoredJobTrackerStore
     * @param JobFlightManager|null $jobFlightManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        PsrContext $context,
        JobStore $jobStore,
        StoredJobSerializer $storedJobSerializer,
        EnqueuedStoredJobTrackerStore $enqueuedStoredJobTrackerStore,
        JobFlightManager $jobFlightManager = null,
        LoggerInterface $logger = null
    ) {
        $this->context = $context;
        $this->jobStore = $jobStore;
        $this->storedJobSerializer = $storedJobSerializer;
        $this->enqueuedStoredJobTrackerStore = $enqueuedStoredJobTrackerStore;
        $this->jobFlightManager = $jobFlightManager ?: new NullJobFlightManager;
        $this->setLogger($logger ?: new NullLogger);
    }

    /**
     * @param string $topicName
     * @return int
     * @throws FailedToEnqueueStoredJobException
     */
    public function execute(string $topicName): int
    {
        sleep(1);
        $uniq = uniqid();
        $enqueuedMessagesCount = 0;
        $lastEnqueuedStoredJob = null;
        $storedJobsToEnqueue = $this->getStoredJobsToEnqueue($topicName);
        if (count($storedJobsToEnqueue) > 0) {
            $this->logger->debug('[' . $uniq . '] <= uniq!');
            $this->logger->debug('[' . $uniq . '] Last job ID to enqueue: ' . $storedJobsToEnqueue[count($storedJobsToEnqueue) - 1]->id());
            $ids = array_map(function (StoredJob $job) {
                return $job->id();
            }, $storedJobsToEnqueue);
            $this->logger->debug('[' . $uniq . '] Job Ids to enqueue: '. implode(',', $ids));
        }
        if (0 === count($storedJobsToEnqueue)) {
            return $enqueuedMessagesCount;
        }
        $producer = $this->createProducer();
        $topic = $this->createTopic($topicName);
        try {
            /** @var PsrMessage[] $messages */
            $messages = [];
            foreach ($storedJobsToEnqueue as $storedJob) {
                $message = $this->createMessage($storedJob);
                $message->setMessageId($storedJob->id());
                if ($message instanceof SqsMessage) {
                    $message->setMessageDeduplicationId(uniqid());
                    $message->setMessageGroupId(
                        is_null($storedJob->fifoGroupId())
                            ? uniqid()
                            : $storedJob->fifoGroupId()
                    );
                }
                $messages[] = [
                    'jobName' => $storedJob->name(),
                    'message' => $message
                ];
            }
            $this->logger->debug('[' . $uniq . '] Counting messages: ' . count($messages));
            if ($producer instanceof SqsProducer) {
                foreach (array_chunk($messages, 10) as $i => $chunk) {
                    $enqueuedMessagesCount = $enqueuedMessagesCount + count($chunk);
                    $lastEnqueuedStoredJob = $storedJobsToEnqueue[$i * 10 + count($chunk) - 1];
                    $producer->sendAll($topic, array_column($chunk, 'message'));
                    foreach ($chunk as $message) {
                        $this->jobFlightManager->departed(
                            $message['message']->getMessageId(),
                            $message['jobName'],
                            $topicName
                        );
                    }
                }
            } else {
                foreach ($messages as $i => $message) {
                    $producer->send($topic, $message['message']);
                    $enqueuedMessagesCount = $enqueuedMessagesCount + 1;
                    $lastEnqueuedStoredJob = $storedJobsToEnqueue[$i];
                    $this->jobFlightManager->departed(
                        $message['message']->getMessageId(),
                        $message['jobName'],
                        $topicName
                    );
                }
            }
        } catch (Throwable $e) {
            throw new FailedToEnqueueStoredJobException($enqueuedMessagesCount, $e);
        } finally {
            if (null !== $lastEnqueuedStoredJob) {
                $this->logger->debug('[' . $uniq . '] Last job ID enqueued: ' . $lastEnqueuedStoredJob->id());
                $this->enqueuedStoredJobTrackerStore->trackLastEnqueuedStoredJob($topicName, $lastEnqueuedStoredJob);
            }
        }

        return $enqueuedMessagesCount;
    }

    /**
     * @param string $topicName
     * @return StoredJob[]
     */
    private function getStoredJobsToEnqueue(string $topicName): array
    {
        return $this->jobStore->storedJobsSince(
            $this->enqueuedStoredJobTrackerStore->lastEnqueuedStoredJobId($topicName)
        );
    }

    /**
     * @return PsrProducer
     */
    protected function createProducer(): PsrProducer
    {
        $producer = $this->context->createProducer();

        return $producer;
    }

    /**
     * @param string $topicName
     * @return PsrTopic
     */
    protected function createTopic(string $topicName): PsrTopic
    {
        $topic = $this->context->createTopic($topicName);

        return $topic;
    }

    /**
     * @param StoredJob $storedJob
     * @return PsrMessage
     */
    protected function createMessage(StoredJob $storedJob): PsrMessage
    {
        $message = $this->context->createMessage($this->storedJobSerializer->serialize($storedJob));

        return $message;
    }
}
