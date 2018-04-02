<?php

namespace Shippinno\Job\Application\Messaging;

use Shippinno\Job\Domain\Model\AbandonedJobMessage;

interface AbandonedMessageDataTransformer
{
    /**
     * @param AbandonedJobMessage $message
     */
    public function write(AbandonedJobMessage $message): void;

    /**
     * @return mixed
     */
    public function read(): mixed;
}
