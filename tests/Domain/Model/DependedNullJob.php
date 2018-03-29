<?php

namespace Shippinno\Job\Test\Domain\Model;

class DependedNullJob extends NullJob
{
    /**
     * {@inheritdoc}
     */
    public function dependentJobs(): array
    {
        return [new NullJob];
    }
}
