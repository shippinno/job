<?php

namespace Shippinno\Job\Domain\Model;

use Throwable;

class AbandonedJobMessageFailedToRequeueException extends Exception
{
    public function __construct(int $id, Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Failed to requeue abandoned job message (%d).', $id),
            0,
            $previous
        );
    }
}
