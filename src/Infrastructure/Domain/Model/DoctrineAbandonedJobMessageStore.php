<?php

namespace Shippinno\Job\Infrastructure\Domain\Model;

use Doctrine\ORM\EntityRepository;
use Shippinno\Job\Domain\Model\AbandonedJobMessage;
use Shippinno\Job\Domain\Model\AbandonedJobMessageNotFoundException;
use Shippinno\Job\Domain\Model\AbandonedJobMessageStore;

class DoctrineAbandonedJobMessageStore extends EntityRepository implements AbandonedJobMessageStore
{
    /**
     * {@inheritdoc}
     */
    public function abandonedJobMessageOfId(int $id): AbandonedJobMessage
    {
        /** @var null|AbandonedJobMessage $abandonedJobMessage */
        $abandonedJobMessage = $this->find($id);
        if (null === $abandonedJobMessage) {
            throw new AbandonedJobMessageNotFoundException($id);
        }

        return $abandonedJobMessage;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->createQueryBuilder('m')->orderBy('m.id')->getQuery()->getResult();
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
