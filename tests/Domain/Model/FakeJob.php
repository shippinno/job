<?php

namespace Shippinno\Job\Test\Domain\Model;

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
     */
    public function __construct(bool $fails = false)
    {
        parent::__construct();
        $this->fails = $fails;
    }

    /**
     * @return bool
     */
    public function fails(): bool
    {
        return $this->fails;
    }
}
