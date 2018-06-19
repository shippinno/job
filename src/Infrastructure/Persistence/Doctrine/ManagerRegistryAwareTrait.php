<?php

namespace Shippinno\Job\Infrastructure\Persistence\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

trait ManagerRegistryAwareTrait
{
    /**
     * @var ManagerRegistry|null $managerRegistry
     */
    protected $managerRegistry;

    /**
     * @param ManagerRegistry|null $managerRegistry
     */
    public function setManagerRegistry(?ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @return void
     */
    public function flush(): void
    {
        if ($this->hasEntityManager()) {
            foreach ($this->managerRegistry->getManagers() as $entityManager) {
                /** @var EntityManager $entityManager */
                $connection = $entityManager->getConnection();
                if (!$connection->ping()) {
                    $connection->close();
                    $connection->connect();
                }
                $entityManager->flush();
            }
        }
    }

    /**
     * @return void
     */
    public function clear(): void
    {
        if ($this->hasEntityManager()) {
            $this->managerRegistry->getManager()->clear();
        }
    }

    /**
     * @return bool
     */
    protected function hasEntityManager(): bool
    {
        return null !== $this->managerRegistry;
    }
}
