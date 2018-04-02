<?php

namespace Shippinno\Job\Application\Messaging;

use Shippinno\Job\Domain\Model\AbandonedJobMessage;

interface AbandonedJobMessageDataTransformer
{
    /**
     * @param AbandonedJobMessage $message
     * @return AbandonedJobMessageDataTransformer
     */
    public function write(AbandonedJobMessage $message): AbandonedJobMessageDataTransformer;

    /**
     * @return mixed
     */
    public function read();
}
