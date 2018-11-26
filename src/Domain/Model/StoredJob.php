<?php

namespace Shippinno\Job\Domain\Model;

use DateTimeImmutable;

class StoredJob
{
    /**
     * @var int|null
     */
    protected $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $body;

    /**
     * @var DateTimeImmutable
     */
    private $createdAt;

    /**
     * @var bool
     */
    private $isExpendable;

    /**
     * @var string|null
     */
    private $fifoGroupId;

    /**
     * @var null|string
     */
    private $deduplicationId;

    /**
     * @param string $name
     * @param string $body
     * @param DateTimeImmutable $createdAt
     * @param bool $isExpendable
     * @param string|null $fifoGroupId
     * @param string|null $deduplicationId
     */
    public function __construct(
        string $name,
        string $body,
        DateTimeImmutable $createdAt,
        bool $isExpendable,
        string $fifoGroupId = null,
        string $deduplicationId = null
    ) {
        $this->name = $name;
        $this->body = $body;
        $this->createdAt = $createdAt;
        $this->isExpendable = $isExpendable;
        $this->fifoGroupId = $fifoGroupId;
        $this->deduplicationId = $deduplicationId;
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
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function body(): string
    {
        return $this->body;
    }

    /**
     * @return DateTimeImmutable
     */
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return bool
     */
    public function isExpendable(): bool
    {
        return $this->isExpendable;
    }

    /**
     * @return null|string
     */
    public function fifoGroupId(): ?string
    {
        return $this->fifoGroupId;
    }

    /**
     * @return null|string
     */
    public function deduplicationId(): ?string
    {
        return $this->deduplicationId;
    }
}
