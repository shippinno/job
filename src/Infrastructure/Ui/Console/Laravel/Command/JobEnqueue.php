<?php

namespace Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Illuminate\Console\Command;
use LogicException;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shippinno\Job\Application\Messaging\EnqueueStoredJobsService;
use Shippinno\Job\Domain\Model\FailedToEnqueueStoredJobException;

class JobEnqueue extends Command
{
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
     * @var ManagerRegistry|null
     */
    private $managerRegistry;

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
        $this->managerRegistry = $managerRegistry;
        $this->setLogger(null !== $logger ? $logger : new NullLogger);
    }

    public function handle()
    {
        $topic = env('JOB_ENQUEUE_TOPIC');
        if (!$topic) {
            throw new LogicException('The env JOB_ENQUEUE_TOPIC is not defined');
        }
        while (true) {
            if (null !== $this->managerRegistry) {
                $this->managerRegistry->getManager()->clear();
            }
            try {
                $enqueuedMessagesCount = $this->service->execute($topic);
                if ($enqueuedMessagesCount > 0) {
                    $this->logger->debug($enqueuedMessagesCount.' jobs enqueued.');
                    if (null !== $this->managerRegistry) {
                        $this->entityManager->flush();
                    }
                }
            } catch (FailedToEnqueueStoredJobException $e) {
                $this->logger->alert('Failed to enqueue stored job, retrying in 60 seconds.', [
                    'exception' => $e,
                ]);
                sleep(60);
                continue;
            }
        }
    }
}