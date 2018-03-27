<?php

namespace Shippinno\Job\Application\Messaging;

use Interop\Queue\Exception as QueueException;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrTopic;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Shippinno\Job\Application\Job\StoredJob;
use Shippinno\Job\Application\Job\JobStore;
use Shippinno\Job\Domain\Model\FailedToEnqueueStoredJobException;

class EnqueueStoredJobsService
{
    /**
     * @var JobStore
     */
    protected $jobStore;

    /**
     * @var EnqueuedStoredJobTrackerStore
     */
    protected $enqueuedStoredJobTrackerStore;

    /**
     * @var PsrContext
     */
    private $context;

    /**
     * @var Serializer
     */
    private $serializer = null;

    /**
     * @param PsrContext $context
     * @param JobStore $jobStore
     * @param EnqueuedStoredJobTrackerStore $enqueuedStoredJobTrackerStore
     */
    public function __construct(
        PsrContext $context,
        JobStore $jobStore,
        EnqueuedStoredJobTrackerStore $enqueuedStoredJobTrackerStore
    ) {
        $this->context = $context;
        $this->jobStore = $jobStore;
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
                $producer->send($topic, $message);
                $enqueuedMessagesCount = $enqueuedMessagesCount + 1;
                $lastEnqueuedStoredJob = $storedJob;
            }
        } catch (QueueException $e) {
            throw new FailedToEnqueueStoredJobException($e);
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
        $message = $this->context->createMessage($this->serializer()->serialize($storedJob, 'json'));

        return $message;
    }

    /**
     * @return Serializer
     */
    private function serializer(): Serializer
    {
        if (null === $this->serializer) {
            $this->serializer =
                SerializerBuilder::create()
                    ->addMetadataDir(__DIR__.'/../../Infrastructure/Serialization/JMS/Config')
                    ->setCacheDir(__DIR__.'/../../../var/cache/jms-serializer')
                    ->build();
        }

        return $this->serializer;
    }
}

