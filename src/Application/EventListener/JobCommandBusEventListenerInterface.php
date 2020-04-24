<?php

declare(strict_types=1);

namespace MicroModule\Task\Application\EventListener;

use MicroModule\Base\Domain\Command\CommandInterface;
use Interop\Queue\Message;

/**
 * Interface JobCommandBusEventListenerInterface.
 */
interface JobCommandBusEventListenerInterface
{
    public const EVENT_PRE_PROCESS = 'enqueue.job.pre_process';
    public const EVENT_POST_PROCESS = 'enqueue.job.post_process';
    public const EVENT_FAILED_PROCESS = 'enqueue.job.failed_process';

    public const METHOD_PRE_PROCESS = 'preProcessCommand';
    public const METHOD_POST_PROCESS = 'postProcessCommand';
    public const METHOD_FAILED_PROCESS = 'failedProcessCommand';

    public const EVENT_TAGS = [
        self::EVENT_PRE_PROCESS => self::METHOD_PRE_PROCESS,
        self::EVENT_POST_PROCESS => self::METHOD_POST_PROCESS,
        self::EVENT_FAILED_PROCESS => self::METHOD_FAILED_PROCESS,
    ];

    /**
     * Job pre process command event action.
     *
     * @param Message          $messagee
     * @param CommandInterface $command
     */
    public function preProcessCommand(Message $messagee, CommandInterface $command): void;

    /**
     * Job post process command event action.
     *
     * @param Message          $message
     * @param CommandInterface $command
     */
    public function postProcessCommand(Message $message, CommandInterface $command): void;

    /**
     * Job failed process command event action.
     *
     * @param Message          $message
     * @param CommandInterface $command
     */
    public function failedProcessCommand(Message $message, CommandInterface $command): void;
}
