<?php

namespace Shippinno\Job\Test\Domain\Model;

use DateTimeImmutable;
use Shippinno\Job\Domain\Model\StoredJob;

class FakeStoredJob extends StoredJob
{
    /**
     * @param string $name
     * @param string $body
     * @param DateTimeImmutable $createdAt
     * @param int|null $id
     */
    public function __construct(string $name, string $body, DateTimeImmutable $createdAt, int $id = null)
    {
        parent::__construct($name, $body, $createdAt);
        $this->id = $id;
    }
}
