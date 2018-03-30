<?php

namespace Shippinno\Job\Domain\Model;

use Throwable;

class AbandonedJobMessageNotFoundException extends Exception
{
    public function __construct(int $id, Throwable $previous = null)
    {
        parent::__construct(sprintf('Abandoned job message not found (%d).', $id), 0, $previous);
    }
}
