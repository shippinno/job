<?php

namespace Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command;

use Illuminate\Console\Command;
use Shippinno\Job\Application\Messaging\RequeueAbandonedJobMessageService;

class JobRequeue extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $signature = 'job:requeue {id}';

    /**
     * @var RequeueAbandonedJobMessageService
     */
    private $requeueAbandonedJobMessageService;

    public function __construct(RequeueAbandonedJobMessageService $requeueAbandonedJobMessageService)
    {
        parent::__construct();
        $this->requeueAbandonedJobMessageService = $requeueAbandonedJobMessageService;
    }

    public function handle()
    {
        $id = intval($this->argument('id'));
        $this->requeueAbandonedJobMessageService->execute($id);
    }
}
