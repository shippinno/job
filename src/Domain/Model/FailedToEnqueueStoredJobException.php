<?php

namespace Shippinno\Job\Domain\Model;

use Throwable;

class FailedToEnqueueStoredJobException extends Exception
{
    /**
     * @var int
     */
    private $enqueuedMessagesCount;

    /**
     * @param string $message
     * @param int $code
     * @param int $enqueuedMessagesCount
     * @param Throwable|null $previous
     */
    public function __construct(int $enqueuedMessagesCount, Throwable $previous = null)
    {
        parent::__construct('Failed to enqueue stored job.', 0, $previous);
        $this->enqueuedMessagesCount = $enqueuedMessagesCount;
    }

    /**
     * @return int
     */
    public function enqueuedMessagesCount(): int
    {
        return $this->enqueuedMessagesCount;
    }
}
