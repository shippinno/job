<?php

namespace Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\Persistence\ManagerRegistry;
use Illuminate\Console\Command;
use Shippinno\Job\Application\Messaging\DeleteAbandonedJobMessageService;
use Shippinno\Job\Domain\Model\AbandonedJobMessageNotFoundException;
use Shippinno\Job\Infrastructure\Persistence\Doctrine\ManagerRegistryAwareTrait;

class JobAbandonedDelete extends Command
{
    use ManagerRegistryAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected $signature = 'job:abandoned:delete {id}';

    /**
     * @var DeleteAbandonedJobMessageService
     */
    private $deleteAbandonedJobMessageService;

    /**
     * @param DeleteAbandonedJobMessageService $deleteAbandonedJobMessageService
     * @param ManagerRegistry|null $managerRegistry
     */
    public function __construct(
        DeleteAbandonedJobMessageService $deleteAbandonedJobMessageService,
        ManagerRegistry $managerRegistry = null
    ) {
        parent::__construct();
        $this->deleteAbandonedJobMessageService = $deleteAbandonedJobMessageService;
        $this->setManagerRegistry($managerRegistry);
    }

    /**
     * @throws AbandonedJobMessageNotFoundException
     */
    public function handle()
    {
        $id = intval($this->argument('id'));
        $this->deleteAbandonedJobMessageService->execute($id);
        $this->flush();
        $this->info('Message has been deleted.');
    }
}
