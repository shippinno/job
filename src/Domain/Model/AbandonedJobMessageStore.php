<?php

namespace Shippinno\Job\Domain\Model;

interface AbandonedJobMessageStore
{
    /**
     * @return AbandonedJobMessage[]
     */
    public function all(): array;

    /**
     * @param AbandonedJobMessage $abandonedJobMessage
     */
    public function add(AbandonedJobMessage $abandonedJobMessage): void;

    /**
     * @param AbandonedJobMessage $abandonedJobMessage
     */
    public function remove(AbandonedJobMessage $abandonedJobMessage): void;
}
