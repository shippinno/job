<?php

namespace Shippinno\Job\Test\Infrastructure\Domain\Model;

use Shippinno\Job\Domain\Model\AbandonedJobMessage;
use Shippinno\Job\Domain\Model\AbandonedJobMessageNotFoundException;
use Shippinno\Job\Domain\Model\AbandonedJobMessageStore;

class InMemoryAbandonedJobMessageStore implements AbandonedJobMessageStore
{
    /**
     * @var AbandonedJobMessage[]
     */
    private $abandonedJobMessages = [];

    /**
     * {@inheritdoc}
     */
    public function abandonedJobMessageOfId(int $id): AbandonedJobMessage
    {
        if (!isset($this->abandonedJobMessages[$id])) {
            throw new AbandonedJobMessageNotFoundException($id);
        }
        return $this->abandonedJobMessages[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->abandonedJobMessages;
    }

    /**
     * {@inheritdoc}
     */
    public function add(AbandonedJobMessage $abandonedJobMessage): void
    {
        if (0 === count($this->abandonedJobMessages)) {
            $nextId = 1;
        } else {
            $maxId = max(array_keys($this->abandonedJobMessages));
            $nextId = $maxId + 1;
        }
        $reflection = new \ReflectionClass($abandonedJobMessage);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($abandonedJobMessage, $nextId);
        $this->abandonedJobMessages[$nextId] = $abandonedJobMessage;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(AbandonedJobMessage $abandonedJobMessage): void
    {
        unset($this->abandonedJobMessages[$abandonedJobMessage->id()]);
    }
}
