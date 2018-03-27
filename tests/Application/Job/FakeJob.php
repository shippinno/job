<?php

namespace Shippinno\Job\Application\Job;

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

    /**
     * @return string
     */
    public function jobRunner(): string
    {
        return FakeJobRunner::class;
    }
}
