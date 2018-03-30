<?php

namespace Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Illuminate\Console\Command;
use Shippinno\Job\Application\Messaging\RequeueAbandonedJobMessageService;
use Shippinno\Job\Infrastructure\Persistence\Doctrine\ManagerRegistryAwareTrait;

class JobRequeue extends Command
{
    use ManagerRegistryAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected $signature = 'job:requeue {id}';

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
