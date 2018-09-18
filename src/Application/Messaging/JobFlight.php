<?php

namespace Shippinno\Job\Application\Messaging;

use DateTimeImmutable;

class JobFlight
{
    /**
     * @string
     */
    private const RESULT_ACKNOWLEDGED = 'acknowledged';

    /**
     * @string
     */
    private const RESULT_ABANDONED = 'abandoned';

    /**
     * @string
     */
    private const RESULT_REQUEUED = 'requeued';

    /**
     * @string
     */
    private const RESULT_REJECTED = 'rejected';

    /**
     * @string
     */
    private const RESULT_LET_GO = 'let_go';

    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $jobId;

    /**
     * @var string
     */
    private $jobName;

    /**
     * @var string
     */
    private $queue;

    /**
     * @var DateTimeImmutable
     */
    private $departure;

    /**
     * @var null|DateTimeImmutable
     */
    private $arrival;

    /**
     * @var string|string
     */
    private $result;

    /**
     * @param int $jobId
     * @param string $jobName
     * @param string $queue
     * @throws \Exception
     */
    public function __construct(int $jobId, string $jobName, string $queue)
    {
        $this->jobId = $jobId;
        $this->jobName = $jobName;
        $this->queue = $queue;
        $this->departure = new DateTimeImmutable;
        $this->arrival = null;
        $this->result = null;
    }

    /**
     * @return int
     */
    public function jobId(): int
    {
        return $this->jobId;
    }

    /**
     * @return string
     */
    public function jobName(): string
    {
        return $this->jobName;
    }

    /**
     * @return string
     */
    public function queue(): string
    {
        return $this->queue;
    }

    /**
     * @return void
     */
    public function acknowledge(): void
    {
        $this->arrival = new DateTimeImmutable;
        $this->result = self::RESULT_ACKNOWLEDGED;
    }

    /**
     * @return void
     */
    public function abandoned(): void
    {
        $this->arrival = new DateTimeImmutable;
        $this->result = self::RESULT_ABANDONED;
    }

    /**
     * @return void
     */
    public function requeued(): void
    {
        $this->arrival = new DateTimeImmutable;
        $this->result = self::RESULT_REQUEUED;
    }

    /**
     * @throws void
     */
    public function rejected(): void
    {
        $this->arrival = new DateTimeImmutable;
        $this->result = self::RESULT_REJECTED;
    }

    /**
     * @return void
     */
    public function letGo(): void
    {
        $this->arrival = new DateTimeImmutable;
        $this->result = self::RESULT_LET_GO;
    }
}
