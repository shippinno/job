<?php

namespace Shippinno\Job\Test\Domain\Model;

use DateTimeImmutable;
use Shippinno\Job\Test\Application\Job\FakeJobRunner;
use Shippinno\Job\Domain\Model\Job;

class FakeJob extends Job
{
    /**
     * @var bool
     */
    private $fails;

    /**
     * @param bool $fails
     * @param DateTimeImmutable|null $createdAt
     */
    public function __construct(bool $fails = false, DateTimeImmutable $createdAt = null)
    {
        parent::__construct();
        $this->fails = $fails;
        if (!is_null($createdAt)) {
            $this->createdAt = $createdAt;
        }
    }

    /**
     * @return bool
     */
    public function fails(): bool
    {
        return $this->fails;
    }
}
