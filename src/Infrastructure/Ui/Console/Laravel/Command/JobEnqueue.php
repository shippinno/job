<?php

namespace Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command;

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
     * @param EnqueueStoredJobsService $service
     * @param LoggerInterface $logger
     */
    public function __construct(EnqueueStoredJobsService $service, LoggerInterface $logger)
    {
        parent::__construct();
        $this->service = $service;
        $this->setLogger($logger);
    }

    public function handle()
    {
        $topic = env('JOB_ENQUEUE_TOPIC');
        if (!$topic) {
            throw new LogicException('The env JOB_ENQUEUE_TOPIC is not defined');
        }
        while (true) {
            try {
                $this->service->execute($topic);
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