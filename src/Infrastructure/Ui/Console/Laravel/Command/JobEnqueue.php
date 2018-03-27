<?php

namespace Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\ORM\EntityManager;
use Illuminate\Console\Command;
use LogicException;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
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
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param EnqueueStoredJobsService $service
     * @param EntityManager $entityManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        EnqueueStoredJobsService $service,
        EntityManager $entityManager,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->service = $service;
        $this->entityManager = $entityManager;
        $this->setLogger($logger);
    }

    public function handle()
    {
        $topic = env('JOB_ENQUEUE_TOPIC');
        if (!$topic) {
            throw new LogicException('The env JOB_ENQUEUE_TOPIC is not defined');
        }
        while (true) {
            $this->entityManager->clear();
            try {
                $enqueuedMessagesCount = $this->service->execute($topic);
                if ($enqueuedMessagesCount > 0) {
                    $this->logger->debug($enqueuedMessagesCount.' jobs enqueued.');
                    $this->entityManager->flush();
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