<?php

namespace Shippinno\Job\Infrastructure\Application\Messaging;

use Shippinno\Job\Application\Messaging\AbandonedJobMessageDataTransformer;
use Shippinno\Job\Domain\Model\AbandonedJobMessage;

class ArrayAbandonedJobMessageDataTransformer implements AbandonedJobMessageDataTransformer
{
    /**
     * @var AbandonedJobMessage
     */
    private $message;

    /**
     * {@inheritdoc}
     * @return self
     */
    public function write(AbandonedJobMessage $message): AbandonedJobMessageDataTransformer
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return array
     */
    public function read()
    {
        return [
            'id' => $this->message->id(),
            'queue' => $this->message->queue(),
            'message' => $this->message->message(),
            'reason' => $this->message->reason(),
            'abandonedAt' => $this->message->abandonedAt(),
        ];
    }
}
