<?php

namespace Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Illuminate\Console\Command;
use LogicException;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shippinno\Job\Application\Messaging\ConsumeStoredJobService;
use Shippinno\Job\Infrastructure\Persistence\Doctrine\ManagerRegistryAwareTrait;

class JobConsume extends Command
{
    use ManagerRegistryAwareTrait;
    use LoggerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected $signature = 'job:consume';

    /**
     * @var ConsumeStoredJobService
     */
    private $consumeStoredJobService;

    /**
     * @param ConsumeStoredJobService $consumeStoredJobService
     * @param ManagerRegistry|null $managerRegistry
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ConsumeStoredJobService $consumeStoredJobService,
        ManagerRegistry $managerRegistry = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct();
        $this->consumeStoredJobService = $consumeStoredJobService;
        $this->setManagerRegistry($managerRegistry);
        $this->setLogger(null !== $logger ? $logger : new NullLogger);
    }

    public function handle()
    {
        $queueName = env('JOB_CONSUME_QUEUE');
        if (!$queueName) {
            throw new LogicException('The env JOB_CONSUME_QUEUE is not defined');
        }
        $forever = !env('JOB_TESTING', false);
        do {
            $this->clear();
            $this->consumeStoredJobService->execute($queueName);
            $this->flush();
        } while ($forever);
    }
}
