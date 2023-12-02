<?php

namespace Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\Persistence\ManagerRegistry;
use Illuminate\Console\Command;
use Shippinno\Job\Application\Messaging\RequeueAbandonedJobMessageService;
use Shippinno\Job\Infrastructure\Persistence\Doctrine\ManagerRegistryAwareTrait;

class JobAbandonedRequeue extends Command
{
    use ManagerRegistryAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected $signature = 'job:abandoned:requeue {id}';

    /**
     * @var RequeueAbandonedJobMessageService
     */
    private $requeueAbandonedJobMessageService;

    /**
     * @param RequeueAbandonedJobMessageService $requeueAbandonedJobMessageService
     * @param ManagerRegistry|null $managerRegistry
     */
    public function __construct(
        RequeueAbandonedJobMessageService $requeueAbandonedJobMessageService,
        ManagerRegistry $managerRegistry = null
    ) {
        parent::__construct();
        $this->requeueAbandonedJobMessageService = $requeueAbandonedJobMessageService;
        $this->setManagerRegistry($managerRegistry);
    }

    /**
     * @throws \Shippinno\Job\Domain\Model\AbandonedJobMessageFailedToRequeueException
     * @throws \Shippinno\Job\Domain\Model\AbandonedJobMessageNotFoundException
     */
    public function handle()
    {
        $id = intval($this->argument('id'));
        $this->requeueAbandonedJobMessageService->execute($id);
        $this->flush();
        $this->info('Message has been successfully enqueued and the abandoned has been deleted.');
    }
}
