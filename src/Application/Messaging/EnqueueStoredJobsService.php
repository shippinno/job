<?php

namespace Shippinno\Job\Application\Messaging;

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
            foreach ($storedJobsToEnqueue as $storedJob) {
                $message = $this->createMessage($storedJob);
                if (method_exists($message, 'setMessageDeduplicationId')) {
                    $message->setMessageDeduplicationId(uniqid());
                }
                if (method_exists($message, 'setMessageGroupId')) {
                    $message->setMessageGroupId(
                        is_null($storedJob->fifoGroupId())
                            ? uniqid()
                            : $storedJob->fifoGroupId()
                    );
                }
                $producer->send($topic, $message);
                $enqueuedMessagesCount = $enqueuedMessagesCount + 1;
                $lastEnqueuedStoredJob = $storedJob;
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
