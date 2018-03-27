<?php

namespace Shippinno\Job\Application\Job;

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
     * @param string $name
     * @param string $body
     * @param DateTimeImmutable $createdAt
     */
    public function __construct(string $name, string $body, DateTimeImmutable $createdAt)
    {
        $this->name = $name;
        $this->body = $body;
        $this->createdAt = $createdAt;
    }

    /**
     * @return int|null
     */
    public function id(): ?int
    {
        return $this->id;
    }
}
