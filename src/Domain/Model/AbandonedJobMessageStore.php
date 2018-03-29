<?php

namespace Shippinno\Job\Domain\Model;

interface AbandonedJobMessageStore
{
    /**
     * @param int $id
     * @return null|AbandonedJobMessage
     * @throws AbandonedJobMessageNotFoundException
     */
    public function abandonedJobMessageOfId(int $id): ?AbandonedJobMessage;

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
