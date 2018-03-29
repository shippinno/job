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
     * @param string $message
     * @param string $reason
     */
    public function __construct(string $message, string $reason)
    {
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
}
