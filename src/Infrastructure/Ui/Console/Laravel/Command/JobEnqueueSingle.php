<?php

namespace Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Illuminate\Console\Command;
use LogicException;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shippinno\Job\Application\Messaging\EnqueueSingleStoredJobService;
use Shippinno\Job\Domain\Model\FailedToEnqueueStoredJobException;
use Shippinno\Job\Domain\Model\StoredJobNotFoundException;
use Shippinno\Job\Infrastructure\Persistence\Doctrine\ManagerRegistryAwareTrait;

class JobEnqueueSingle extends Command
{
    use ManagerRegistryAwareTrait;
    use LoggerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected $signature = 'job:enqueue:single {jobId}';

    /**
     * @var EnqueueSingleStoredJobService
     */
    private $service;

    /**
     * @param EnqueueSingleStoredJobService $service
     * @param ManagerRegistry|null $managerRegistry
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        EnqueueSingleStoredJobService $service,
        ManagerRegistry $managerRegistry = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct();
        $this->service = $service;
        $this->setManagerRegistry($managerRegistry);
        $this->setLogger(null !== $logger ? $logger : new NullLogger);
    }

    /**
     * @throws FailedToEnqueueStoredJobException
     * @throws StoredJobNotFoundException
     */
    public function handle()
    {
        $this->service->execute($this->topicName(), intval($this->argument('jobId')));
    }

    /**
     * @return string
     */
    private function topicName(): string
    {
        $topicName = env('JOB_ENQUEUE_TOPIC');
        if (!$topicName) {
            throw new LogicException('The env JOB_ENQUEUE_TOPIC is not defined');
        }

        return $topicName;
    }
}
