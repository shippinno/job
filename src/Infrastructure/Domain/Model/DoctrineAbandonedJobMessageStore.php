<?php

namespace Shippinno\Job\Infrastructure\Domain\Model;

use Doctrine\ORM\EntityRepository;
use Shippinno\Job\Domain\Model\AbandonedJobMessage;
use Shippinno\Job\Domain\Model\AbandonedJobMessageStore;

class DoctrineAbandonedJobMessageStore extends EntityRepository implements AbandonedJobMessageStore
{
    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->findAll();
    }

    /**
     * {@inheritdoc}
     */
    public function add(AbandonedJobMessage $abandonedJobMessage): void
    {
        $this->getEntityManager()->persist($abandonedJobMessage);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(AbandonedJobMessage $abandonedJobMessage): void
    {
        $this->getEntityManager()->remove($abandonedJobMessage);
    }
}
