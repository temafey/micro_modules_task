<?php

declare(strict_types=1);

namespace MicroModule\Task\Application\Processor;

use MicroModule\Base\Domain\Exception\LoggerException;
use MicroModule\Base\Utils\LoggerTrait;
use Enqueue\Client\ProducerInterface;
use Enqueue\JobQueue\Doctrine\JobStorage;
use Enqueue\JobQueue\Job;

/**
 * Class JobProcessor.
 *
 * @SuppressWarnings(PHPMD)
 */
class JobProcessor extends \Enqueue\JobQueue\JobProcessor
{
    public const JOB_TASK_STATUS_STARTED = 'started';
    public const JOB_TASK_STATUS_FAILED = 'failed';
    public const JOB_TASK_STATUS_SUCCESS = 'success';
    public const JOB_TASK_STATUS_CANCELED = 'canceled';

    use LoggerTrait;

    /**
     * @var ProducerInterface
     */
    private $taskEventProducer;

    /**
     * JobProcessor constructor.
     *
     * @param JobStorage        $jobStorage
     * @param ProducerInterface $taskEventProducer
     */
    public function __construct(JobStorage $jobStorage, ProducerInterface $taskEventProducer)
    {
        parent::__construct($jobStorage, $taskEventProducer);
        $this->taskEventProducer = $taskEventProducer;
    }

    /**
     * @param Job $job
     *
     * @throws LoggerException
     */
    public function startChildJob(Job $job): void
    {
        parent::startChildJob($job); // TODO: Change the autogenerated stub

        [$name, $uuid] = explode('_', $job->getName());
        $this->logMessage(sprintf('Start job `%s`, uuid `%s`, jobId `%s`', $name, $uuid, $job->getId()), LOG_INFO);
        $this->taskEventProducer->sendEvent(JobEventProcessor::SUBSCRIBED_TASK_EVENT_COMMAND,
            [
                'name' => $name,
                'uuid' => $uuid,
                'id' => $job->getId(),
                'status' => self::JOB_TASK_STATUS_STARTED,
            ]);
    }

    /**
     * @param Job $job
     *
     * @throws LoggerException
     */
    public function successChildJob(Job $job): void
    {
        parent::successChildJob($job); // TODO: Change the autogenerated stub
        [$name, $uuid] = explode('_', $job->getName());
        $this->logMessage(sprintf('Finish successfully job `%s`, uuid `%s`, jobId `%s`', $name, $uuid, $job->getId()), LOG_INFO);
        $this->taskEventProducer->sendEvent(JobEventProcessor::SUBSCRIBED_TASK_EVENT_COMMAND,
            [
                'name' => $name,
                'uuid' => $uuid,
                'id' => $job->getId(),
                'status' => self::JOB_TASK_STATUS_SUCCESS,
            ]);
    }

    /**
     * @param Job $job
     *
     * @throws LoggerException
     */
    public function failChildJob(Job $job): void
    {
        parent::failChildJob($job); // TODO: Change the autogenerated stub
        [$name, $uuid] = explode('_', $job->getName());
        $this->logMessage(sprintf('Finish unsuccessfully job `%s`, uuid `%s`, jobId `%s`', $name, $uuid, $job->getId()), LOG_INFO);
        $this->taskEventProducer->sendEvent(JobEventProcessor::SUBSCRIBED_TASK_EVENT_COMMAND,
            [
                'name' => $name,
                'uuid' => $uuid,
                'id' => $job->getId(),
                'status' => self::JOB_TASK_STATUS_FAILED,
            ]);
    }

    /**
     * @param Job $job
     *
     * @throws LoggerException
     */
    public function cancelChildJob(Job $job): void
    {
        parent::cancelChildJob($job); // TODO: Change the autogenerated stub
        [$name, $uuid] = explode('_', $job->getName());
        $this->logMessage(sprintf('Canceled job `%s`, uuid `%s`, jobId `%s`', $name, $uuid, $job->getId()), LOG_INFO);
        $this->taskEventProducer->sendEvent(JobEventProcessor::SUBSCRIBED_TASK_EVENT_COMMAND,
            [
                'name' => $name,
                'uuid' => $uuid,
                'id' => $job->getId(),
                'status' => self::JOB_TASK_STATUS_CANCELED,
            ]);
    }

    protected function sendCalculateRootJobStatusEvent(Job $job): void
    {
        // remove send jobId to queue
    }
}
