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
     * @var string|null
     */
    private $fifoGroupId;

    /**
     * @param string $name
     * @param string $body
     * @param DateTimeImmutable $createdAt
     */
    public function __construct(string $name, string $body, DateTimeImmutable $createdAt, string $fifoGroupId = null)
    {
        $this->name = $name;
        $this->body = $body;
        $this->createdAt = $createdAt;
        $this->fifoGroupId = $fifoGroupId;
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
     * @return null|string
     */
    public function fifoGroupId(): ?string
    {
        return $this->fifoGroupId;
    }
}
