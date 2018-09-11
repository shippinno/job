<?php

namespace Shippinno\Job\Application\Messaging;

use Enqueue\Sqs\SqsMessage;
use Enqueue\Sqs\SqsProducer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrTopic;
use Shippinno\Job\Domain\Model\StoredJob;
use Shippinno\Job\Domain\Model\JobStore;
use Shippinno\Job\Domain\Model\FailedToEnqueueStoredJobException;
use Shippinno\Job\Domain\Model\StoredJobSerializer;
use Throwable;

class EnqueueStoredJobsService
{
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
     * @param PsrContext $context
     * @param JobStore $jobStore
     * @param StoredJobSerializer $storedJobSerializer
     * @param EnqueuedStoredJobTrackerStore $enqueuedStoredJobTrackerStore
     */
    public function __construct(
        PsrContext $context,
        JobStore $jobStore,
        StoredJobSerializer $storedJobSerializer,
        EnqueuedStoredJobTrackerStore $enqueuedStoredJobTrackerStore
    ) {
        $this->context = $context;
        $this->jobStore = $jobStore;
        $this->storedJobSerializer = $storedJobSerializer;
        $this->enqueuedStoredJobTrackerStore = $enqueuedStoredJobTrackerStore;
    }

    /**
     * @param string $topicName
     * @return int
     * @throws FailedToEnqueueStoredJobException
     */
    public function execute(string $topicName): int
    {
        $enqueuedMessagesCount = 0;
        $lastEnqueuedStoredJob = null;
        $storedJobsToEnqueue = $this->getStoredJobsToEnqueue($topicName);
        if (0 === count($storedJobsToEnqueue)) {
            return $enqueuedMessagesCount;
        }
        $producer = $this->createProducer();
        $topic = $this->createTopic($topicName);
        try {
            $messages = [];
            foreach ($storedJobsToEnqueue as $storedJob) {
                $message = $this->createMessage($storedJob);
                if ($message instanceof SqsMessage) {
                    $message->setMessageId($storedJob->id());
                    $message->setMessageDeduplicationId(uniqid());
                    $message->setMessageGroupId(
                        is_null($storedJob->fifoGroupId())
                            ? uniqid()
                            : $storedJob->fifoGroupId()
                    );
                }
                $messages[] = $message;
            }
            if ($producer instanceof SqsProducer) {
                foreach (array_chunk($messages, 10) as $i => $chunk) {
                    $enqueuedMessagesCount = $enqueuedMessagesCount + count($chunk);
                    $lastEnqueuedStoredJob = $storedJobsToEnqueue[$i * 10 + count($chunk) - 1];
                    $producer->sendAll($topic, $chunk);
                }
            } else {
                foreach ($messages as $i => $message) {
                    $producer->send($topic, $message);
                    $enqueuedMessagesCount = $enqueuedMessagesCount + 1;
                    $lastEnqueuedStoredJob = $storedJobsToEnqueue[$i];
                }
            }
        } catch (Throwable $e) {
            throw new FailedToEnqueueStoredJobException($enqueuedMessagesCount, $e);
        } finally {
            if (null !== $lastEnqueuedStoredJob) {
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
