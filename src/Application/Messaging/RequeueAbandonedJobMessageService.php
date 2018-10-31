<?php

namespace Shippinno\Job\Application\Messaging;

use Interop\Queue\Exception as QueueException;
use Interop\Queue\Context;
use Shippinno\Job\Domain\Model\AbandonedJobMessageFailedToRequeueException;
use Shippinno\Job\Domain\Model\AbandonedJobMessageNotFoundException;
use Shippinno\Job\Domain\Model\AbandonedJobMessageStore;
use Shippinno\Job\Domain\Model\StoredJobSerializer;

class RequeueAbandonedJobMessageService
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var AbandonedJobMessageStore
     */
    private $abandonedJobMessageStore;

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
     * @param AbandonedJobMessageStore $abandonedJobMessageStore
     * @param StoredJobSerializer $storedJobSerializer
     * @param JobFlightManager|null $jobFlightManager
     */
    public function __construct(
        Context $context,
        AbandonedJobMessageStore $abandonedJobMessageStore,
        StoredJobSerializer $storedJobSerializer,
        JobFlightManager $jobFlightManager = null
    ) {
        $this->context = $context;
        $this->abandonedJobMessageStore = $abandonedJobMessageStore;
        $this->storedJobSerializer = $storedJobSerializer;
        $this->jobFlightManager = $jobFlightManager ?: new NullJobFlightManager;
    }

    /**
     * @param int $abandonedJobMessageId
     * @throws AbandonedJobMessageFailedToRequeueException
     * @throws AbandonedJobMessageNotFoundException
     */
    public function execute(int $abandonedJobMessageId): void
    {
        $abandonedJobMessage = $this->abandonedJobMessageStore->abandonedJobMessageOfId($abandonedJobMessageId);
        $queue = $this->context->createQueue($abandonedJobMessage->queue());
        $message = $this->context->createMessage($abandonedJobMessage->message());
        if (method_exists($message, 'setMessageDeduplicationId')) {
            $message->setMessageDeduplicationId(uniqid());
        }
        if (method_exists($message, 'setMessageGroupId')) {
            $message->setMessageGroupId(uniqid());
        }
        try {
            $storedJob = $this->storedJobSerializer->deserialize($message->getBody());
            $message->setMessageId($storedJob->id());
            $this->context->createProducer()->send($queue, $message);
            $this->abandonedJobMessageStore->remove($abandonedJobMessage);
        } catch (QueueException $e) {
            throw new AbandonedJobMessageFailedToRequeueException($abandonedJobMessage->id(), $e);
        }
    }
}
