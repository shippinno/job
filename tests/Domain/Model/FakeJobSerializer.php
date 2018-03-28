<?php

namespace Shippinno\Job\Test\Domain\Model;

use Shippinno\Job\Domain\Model\Job;
use Shippinno\Job\Domain\Model\JobSerializer;

class FakeJobSerializer implements JobSerializer
{
    /**
     * {@inheritdoc}
     */
    public function serialize(Job $job): string
    {
        return serialize($job);
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize(string $data, string $class): Job
    {
        return unserialize($data);
    }
}
