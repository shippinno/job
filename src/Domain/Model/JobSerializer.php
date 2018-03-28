<?php

namespace Shippinno\Job\Domain\Model;

interface JobSerializer
{
    /**
     * @param Job $job
     * @return string
     */
    public function serialize(Job $job): string;

    /**
     * @param string $data
     * @param string $class
     * @return Job
     */
    public function deserialize(string $data, string $class): Job;
}
