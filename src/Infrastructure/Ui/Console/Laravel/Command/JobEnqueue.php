<?php

namespace Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\Persistence\ManagerRegistry;
use Illuminate\Console\Command;
use LogicException;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shippinno\Job\Application\Messaging\EnqueueStoredJobsService;
use Shippinno\Job\Domain\Model\FailedToEnqueueStoredJobException;
use Shippinno\Job\Infrastructure\Persistence\Doctrine\ManagerRegistryAwareTrait;

class JobEnqueue extends Command
{
    use ManagerRegistryAwareTrait;
    use LoggerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected $signature = 'job:enqueue';

    /**
     * @var EnqueueStoredJobsService
     */
    private $service;

    /**
     * @param EnqueueStoredJobsService $service
     * @param ManagerRegistry|null $managerRegistry
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        EnqueueStoredJobsService $service,
        ManagerRegistry $managerRegistry = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct();
        $this->service = $service;
        $this->setManagerRegistry($managerRegistry);
        $this->setLogger(null !== $logger ? $logger : new NullLogger);
    }

    public function handle()
    {
        $topicName = $this->topicName();
        $testing = env('JOB_TESTING', false);
        do {
            $this->clear();
            try {
                $enqueuedMessagesCount = $this->service->execute($topicName);
                $this->flushAndLog(
                    $enqueuedMessagesCount > 0,
                    sprintf('%d job(s) enqueued.', $enqueuedMessagesCount)
                );
            } catch (FailedToEnqueueStoredJobException $e) {
                $enqueuedMessagesCount = $e->enqueuedMessagesCount();
                $this->flushAndLog(
                    $enqueuedMessagesCount > 0,
                    sprintf('%d job(s) enqueued.', $enqueuedMessagesCount)
                );
                $interval = !$testing ? 60 : 0;
                $this->logger->alert(
                    sprintf('Failed to enqueue stored job, retrying in %d second(s).', $interval),
                    ['exception' => $e]
                );
                sleep($interval);
                continue;
            }
        } while (!$testing);
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

    /**
     * @param bool $condition
     * @param string $logMessage
     */
    private function flushAndLog(bool $condition, string $logMessage): void
    {
        if (!$condition) {
            return;
        }
        $this->flush();
        $this->logger->debug($logMessage);
    }

}
