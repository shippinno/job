<?php

namespace Shippinno\Job\Application\Messaging;

use Shippinno\Job\Domain\Model\AbandonedJobMessageNotFoundException;
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
     * @param int $id
     * @throws AbandonedJobMessageNotFoundException
     */
    public function execute(int $id): void
    {
        $abandonedJobMessage = $this->abandonedJobMessageStore->abandonedJobMessageOfId($id);
        $this->abandonedJobMessageStore->remove($abandonedJobMessage);
    }
}
