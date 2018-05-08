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
     * @var null|string
     */
    protected $fifoGroupId;

    /**
     * @var DateTimeImmutable
     */
    protected $createdAt;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable;
        $this->setFifoGroupId();
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
     * @return null|string
     */
    public function fifoGroupId(): ?string
    {
        return $this->fifoGroupId;
    }

    /**
     *
     */
    protected function setFifoGroupId(): void
    {
        $this->fifoGroupId = null;
    }

    /**
     * @return DateTimeImmutable
     */
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Job[]
     */
    public function dependentJobs(): array
    {
        return [];
    }
}
