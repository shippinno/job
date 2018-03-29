<?php

namespace Shippinno\Job\Application\Messaging;

use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;
use Shippinno\Job\Application\Job\JobRunnerRegistry;
use Shippinno\Job\Domain\Model\AbandonedJobMessage;
use Shippinno\Job\Domain\Model\AbandonedJobMessageStore;
use Shippinno\Job\Domain\Model\JobFailedException;
use Shippinno\Job\Domain\Model\JobRunnerNotRegisteredException;
use Shippinno\Job\Domain\Model\JobSerializer;
use Shippinno\Job\Domain\Model\JobStore;
use Shippinno\Job\Domain\Model\StoredJobSerializer;

class ConsumeStoredJobService
{
    /**
     * @var StoredJobSerializer
     */
    private $storedJobSerializer;

    /**
     * @var JobSerializer
     */
    private $jobSerializer;

    /**
     * @var JobRunnerRegistry
     */
    private $jobRunnerRegistry;

    /**
     * @var JobStore
     */
    private $jobStore;

    /**
     * @var AbandonedJobMessageStore
     */
    private $abandonedJobMessageStore;

    /**
     * @param StoredJobSerializer $storedJobSerializer
     * @param JobSerializer $jobSerializer
     * @param JobRunnerRegistry $jobRunnerRegistry
     * @param JobStore $jobStore
     * @param AbandonedJobMessageStore $abandonedJobMessageStore
     */
    public function __construct(
        StoredJobSerializer $storedJobSerializer,
        JobSerializer $jobSerializer,
        JobRunnerRegistry $jobRunnerRegistry,
        JobStore $jobStore,
        AbandonedJobMessageStore $abandonedJobMessageStore
    ) {
        $this->storedJobSerializer = $storedJobSerializer;
        $this->jobSerializer = $jobSerializer;
        $this->jobRunnerRegistry = $jobRunnerRegistry;
        $this->jobStore = $jobStore;
        $this->abandonedJobMessageStore = $abandonedJobMessageStore;
    }

    /**
     * @param PsrConsumer $consumer
     */
    public function execute(PsrConsumer $consumer)
    {
        $message = $consumer->receive();
        if (null === $message) {
            return;
        }
        $storedJob = $this->storedJobSerializer->deserialize($message->getBody());
        $job = $this->jobSerializer->deserialize($storedJob->body(), $storedJob->name());
        try {
            $jobRunner = $this->jobRunnerRegistry->get(get_class($job));
        } catch (JobRunnerNotRegisteredException $e) {
            $this->abandonedJobMessageStore->add(
                new AbandonedJobMessage($message->getBody(), $e->__toString())
            );
            $consumer->reject($message);
            return;
        }
        try {
            $jobRunner->run($job);
            $dependentJobs = $job->dependentJobs();
            if (count($dependentJobs) > 0) {
                foreach ($dependentJobs as $dependentJob) {
                    $this->jobStore->append($dependentJob);
                }
            }
            $consumer->acknowledge($message);
        } catch (JobFailedException $e) {
            $attempts = $message->getProperty('attempts', 0) + 1;
            if ($attempts > $job->maxAttempts()) {
                $this->abandonedJobMessageStore->add(
                    new AbandonedJobMessage($message->getBody(), $e->__toString())
                );
                $consumer->reject($message);
                return;
            }
            $message->setProperty('attempts', $attempts);
            if ($job->reattemptDelay() > 0) {
                $message = $this->delayMessage($message, $job->reattemptDelay());
            }
            $consumer->reject($message, true);
        }
    }

    /**
     * @param PsrMessage $message
     * @param int $delay
     * @return PsrMessage
     */
    protected function delayMessage(PsrMessage $message, int $delay)
    {
        if (method_exists($message, 'setDelaySeconds')) {
            $message->setDelaySeconds($delay);
        }

        return $message;
    }
}
