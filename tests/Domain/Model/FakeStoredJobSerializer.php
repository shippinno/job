<?php

namespace Shippinno\Job\Test\Domain\Model;

use Shippinno\Job\Domain\Model\StoredJob;
use Shippinno\Job\Domain\Model\StoredJobSerializer;

class FakeStoredJobSerializer implements StoredJobSerializer
{
    /**
     * {@inheritdoc}
     */
    public function serialize(StoredJob $storedJob): string
    {
        return serialize($storedJob);
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize(string $data): StoredJob
    {
        return unserialize($data);
    }
}
