<?php

namespace Shippinno\Job\Application\Messaging;

use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrTopic;
use Shippinno\Job\Domain\Model\FailedToEnqueueStoredJobException;
use Shippinno\Job\Domain\Model\JobStore;
use Shippinno\Job\Domain\Model\StoredJob;
use Shippinno\Job\Domain\Model\StoredJobNotFoundException;
use Shippinno\Job\Domain\Model\StoredJobSerializer;
use Throwable;

class EnqueueSingleStoredJobService
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
     * @var JobFlightManager
     */
    private $jobFlightManager;

    /**
     * @param PsrContext $context
     * @param JobStore $jobStore
     * @param StoredJobSerializer $storedJobSerializer
     * @param JobFlightManager|null $jobFlightManager
     */
    public function __construct(
        PsrContext $context,
        JobStore $jobStore,
        StoredJobSerializer $storedJobSerializer,
        JobFlightManager $jobFlightManager = null
    ) {
        $this->context = $context;
        $this->jobStore = $jobStore;
        $this->storedJobSerializer = $storedJobSerializer;
        $this->jobFlightManager = $jobFlightManager ?: new NullJobFlightManager;
    }

    /**
     * @param string $topicName
     * @param int|null $storedJobId
     * @throws FailedToEnqueueStoredJobException
     * @throws StoredJobNotFoundException
     */
    public function execute(string $topicName, int $storedJobId = null)
    {
        $storedJob = $this->jobStore->storedJobOfId($storedJobId);
        if (is_null($storedJob)) {
            throw new StoredJobNotFoundException;
        }
        $producer = $this->createProducer();
        $topic = $this->createTopic($topicName);
        $message = $this->createMessage($storedJob);
        $message->setMessageId($storedJob->id());
        $message->setMessageDeduplicationId(uniqid());
        $message->setMessageGroupId(
            is_null($storedJob->fifoGroupId())
                ? uniqid()
                : $storedJob->fifoGroupId()
        );
        try {
            $producer->send($topic, $message);
            $this->jobFlightManager->departed($message->getMessageId());
        } catch (Throwable $e) {
            throw new FailedToEnqueueStoredJobException(0, $e);
        }
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
