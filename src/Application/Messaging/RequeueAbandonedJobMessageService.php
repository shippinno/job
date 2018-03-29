<?php

namespace Shippinno\Job\Application\Messaging;

use Interop\Queue\Exception as QueueException;
use Interop\Queue\PsrContext;
use Shippinno\Job\Domain\Model\AbandonedJobMessageStore;

class RequeueAbandonedJobMessageService
{
    /**
     * @var PsrContext
     */
    private $context;

    /**
     * @var AbandonedJobMessageStore
     */
    private $abandonedJobMessageStore;

    /**
     * @param PsrContext $context
     * @param AbandonedJobMessageStore $abandonedJobMessageStore
     */
    public function __construct(
        PsrContext $context,
        AbandonedJobMessageStore $abandonedJobMessageStore
    ) {
        $this->context = $context;
        $this->abandonedJobMessageStore = $abandonedJobMessageStore;
    }

    /**
     * @param int $abandonedJobMessageId
     * @throws \Shippinno\Job\Domain\Model\AbandonedJobMessageNotFoundException
     */
    public function execute(int $abandonedJobMessageId)
    {
        $abandonedJobMessage = $this->abandonedJobMessageStore->abandonedJobMessageOfId($abandonedJobMessageId);
        $queue = $this->context->createQueue($abandonedJobMessage->queue());
        $message = $this->context->createMessage($$abandonedJobMessage->message());
        try {
            $this->context->createProducer()->send($queue, $message);
        } catch (QueueException $e) {
//            throw new FailedToEnqueueStoredJobException($e);
        }
    }
}
