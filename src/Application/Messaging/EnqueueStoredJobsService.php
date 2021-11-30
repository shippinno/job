<?php

namespace Shippinno\Job\Application\Messaging;

use Enqueue\Sqs\SqsMessage;
use Enqueue\Sqs\SqsProducer;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Topic;
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
     * @var Context
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
     * @var JobFlightManager
     */
    private $jobFlightManager;

    /**
     * @param Context $context
     * @param JobStore $jobStore
     * @param StoredJobSerializer $storedJobSerializer
     * @param JobFlightManager|null $jobFlightManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JobStore $jobStore,
        StoredJobSerializer $storedJobSerializer,
        EnqueuedStoredJobTrackerStore $enqueuedStoredJobTrackerStore,
        JobFlightManager $jobFlightManager = null,
        LoggerInterface $logger = null
    ) {
        $this->context = $context;
        $this->jobStore = $jobStore;
        $this->storedJobSerializer = $storedJobSerializer;
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
        $this->logger->debug('Enqueueing jobs: ' . implode(',', $preBoardingJobIds));
        $storedJobsToEnqueue = $this->jobStore->storedJobsOfIds($preBoardingJobIds);
        $producer = $this->createProducer();
        $topic = $this->createTopic($topicName);
        try {
            /** @var Message[] $messages */
            $messages = [];
            foreach ($storedJobsToEnqueue as $storedJob) {
                $this->jobFlightManager->boarding($storedJob->id());
                $message = $this->createMessage($storedJob);
                $message->setMessageId($storedJob->id());
                if ($message instanceof SqsMessage) {
                    $message->setMessageDeduplicationId(
                        is_null($storedJob->deduplicationId())
                            ? uniqid()
                            : $storedJob->deduplicationId()
                    );
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
                    $enqueuedMessageIds = $producer->sendAll($topic, array_column($chunk, 'message'));
                    foreach ($enqueuedMessageIds as $messageId) {
                        $this->jobFlightManager->departed($messageId);
                        $enqueuedMessagesCount = $enqueuedMessagesCount + 1;
                    }
                }
            } else {
                foreach ($messages as $i => $message) {
                    $producer->send($topic, $message['message']);
                    $enqueuedMessagesCount = $enqueuedMessagesCount + 1;
                    $this->jobFlightManager->departed($message['message']->getMessageId());
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
     * @return Producer
     */
    protected function createProducer(): Producer
    {
        $producer = $this->context->createProducer();

        return $producer;
    }

    /**
     * @param string $topicName
     * @return Topic
     */
    protected function createTopic(string $topicName): Topic
    {
        $topic = $this->context->createTopic($topicName);

        return $topic;
    }

    /**
     * @param StoredJob $storedJob
     * @return Message
     */
    protected function createMessage(StoredJob $storedJob): Message
    {
        $message = $this->context->createMessage($this->storedJobSerializer->serialize($storedJob));

        return $message;
    }
}
