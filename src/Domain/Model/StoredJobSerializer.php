<?php

namespace Shippinno\Job\Domain\Model;

interface StoredJobSerializer
{
    /**
     * @param StoredJob $storedJob
     * @return string
     */
    public function serialize(StoredJob $storedJob): string;

    /**
     * @param string $data
     * @return StoredJob
     */
    public function deserialize(string $data): StoredJob;
}
