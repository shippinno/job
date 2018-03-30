<?php

namespace Shippinno\Job\Application\Messaging;

use Shippinno\Job\Domain\Model\AbandonedJobMessageStore;

class DeleteAbandonedJobMessageService
{
    /**
     * @var AbandonedJobMessageStore
     */
    private $abandonedJobMessageStore;

    /**
     * DeleteAbandonedJobMessageService constructor.
     * @param AbandonedJobMessageStore $abandonedJobMessageStore
     */
    public function __construct(AbandonedJobMessageStore $abandonedJobMessageStore)
    {
        $this->abandonedJobMessageStore = $abandonedJobMessageStore;
    }

    /**
     * @param string $id
     * @throws \Shippinno\Job\Domain\Model\AbandonedJobMessageNotFoundException
     */
    public function execute(string $id): void
    {
        $abandonedJobMessage = $this->abandonedJobMessageStore->abandonedJobMessageOfId($id);
        $this->abandonedJobMessageStore->remove($abandonedJobMessage);
    }
}
