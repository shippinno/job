<?php

namespace Shippinno\Job\Domain\Model;

use DateTimeImmutable;

class AbandonedJobMessage
{
    /**
     * @var int|null
     */
    protected $id;

    /**
     * @var string
     */
    private $queue;

    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $reason;

    /**
     * @var DateTimeImmutable
     */
    private $abandonedAt;

    /**
     * @param string $queue
     * @param string $message
     * @param string $reason
     */
    public function __construct(string $queue, string $message, string $reason)
    {
        $this->queue = $queue;
        $this->message = $message;
        $this->reason = $reason;
        $this->abandonedAt = new DateTimeImmutable;
    }

    /**
     * @return int|null
     */
    public function id(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function queue(): string
    {
        return $this->queue;
    }

    /**
     * @return string
     */
    public function message(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function reason(): string
    {
        return $this->reason;
    }

    /**
     * @return DateTimeImmutable
     */
    public function abandonedAt(): DateTimeImmutable
    {
        return $this->abandonedAt;
    }
}
