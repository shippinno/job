<?php

namespace Shippinno\Job\Application\Messaging;

use Shippinno\Job\Domain\Model\AbandonedJobMessage;
use Shippinno\Job\Domain\Model\AbandonedJobMessageStore;

class ListAbandonedJobMessagesService
{
    /**
     * @var AbandonedJobMessageStore
     */
    private $abandonedJobMessageStore;

    /**
     * @var AbandonedJobMessageDataTransformer
     */
    private $abandonedJobMessageDataTransformer;

    /**
     * @param AbandonedJobMessageStore $abandonedJobMessageStore
     * @param AbandonedJobMessageDataTransformer $abandonedJobMessageDataTransformer
     */
    public function __construct(
        AbandonedJobMessageStore $abandonedJobMessageStore,
        AbandonedJobMessageDataTransformer $abandonedJobMessageDataTransformer
    ) {
        $this->abandonedJobMessageStore = $abandonedJobMessageStore;
        $this->abandonedJobMessageDataTransformer = $abandonedJobMessageDataTransformer;
    }

    /**
     * @return array
     */
    public function execute(): array
    {
        $messages = $this->abandonedJobMessageStore->all();

        return array_map(function (AbandonedJobMessage $message) {
            return $this->abandonedJobMessageDataTransformer->write($message)->read();
        }, $messages);
    }
}
