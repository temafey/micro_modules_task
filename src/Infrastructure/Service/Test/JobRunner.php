<?php

declare(strict_types=1);

namespace MicroModule\Task\Infrastructure\Service\Test;

use Enqueue\JobQueue\Job;
use Enqueue\JobQueue\JobRunner as BaseJobRunner;
use Exception;

/**
 * Class JobRunner.
 *
 * @category Infrastructure\Service\Test
 */
class JobRunner extends BaseJobRunner
{
    /**
     * @var mixed[]
     */
    private $runUniqueJobs = [];

    /**
     * @var mixed[]
     */
    private $createDelayedJobs = [];

    /**
     * @var mixed[]
     */
    private $runDelayedJobs = [];

    /**
     * {@inheritdoc}
     */
    public function runUnique($ownerId, $jobName, callable $runCallback)
    {
        $this->runUniqueJobs[] = ['ownerId' => $ownerId, 'jobName' => $jobName, 'runCallback' => $runCallback];
        $job = new Job();
        $job->setId(random_int(1, 100));

        return $runCallback($this, $job);
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function createDelayed($jobName, callable $startCallback)
    {
        $this->createDelayedJobs[] = ['jobName' => $jobName, 'runCallback' => $startCallback];
        $job = new Job();
        $job->setId(random_int(1, 100));

        return $startCallback($this, $job);
    }

    /**
     * {@inheritdoc}
     *
     * @param string   $jobId
     * @param callable $runCallback
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function runDelayed($jobId, callable $runCallback)
    {
        $this->runDelayedJobs[] = ['jobId' => $jobId, 'runCallback' => $runCallback];
        $job = new Job();
        $job->setId(random_int(1, 100));

        return $runCallback($this, $job);
    }

    /**
     * @return mixed[]
     */
    public function getRunUniqueJobs(): array
    {
        return $this->runUniqueJobs;
    }

    /**
     * @return mixed[]
     */
    public function getCreateDelayedJobs(): array
    {
        return $this->createDelayedJobs;
    }

    /**
     * @return mixed[]
     */
    public function getRunDelayedJobs(): array
    {
        return $this->runDelayedJobs;
    }
}
