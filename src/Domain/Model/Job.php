<?php

namespace Shippinno\Job\Domain\Model;

use DateTimeImmutable;

abstract class Job
{
    /**
     * @var int
     */
    protected $maxAttempts = 1;

    /**
     * @var int
     */
    protected $reattemptDelay = 0;

    /**
     * @var DateTimeImmutable
     */
    protected $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable;
    }

    /**
     * @return int
     */
    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * @param int $maxAttempts
     */
    public function setMaxAttempts(int $maxAttempts): void
    {
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * @return int
     */
    public function reattemptDelay(): int
    {
        return $this->reattemptDelay;
    }

    /**
     * @param int $reattemptDelay
     */
    public function setReattemptDelay(int $reattemptDelay): void
    {
        $this->reattemptDelay = $reattemptDelay;
    }

    /**
     * @return DateTimeImmutable
     */
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
