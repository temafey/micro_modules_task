<?php

declare(strict_types=1);

namespace MicroModule\Task\Application\Processor;

use MicroModule\Base\Domain\Command\CommandInterface;
use MicroModule\Base\Domain\Factory\CommandFactoryInterface;
use MicroModule\Base\Utils\LoggerTrait;
use MicroModule\Task\Application\EventListener\JobCommandBusEventListenerInterface;
use Assert\Assertion;
use Assert\AssertionFailedException;
use Broadway\EventDispatcher\EventDispatcher;
use Closure;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\JobQueue\Job;
use Enqueue\JobQueue\JobRunner;
use Enqueue\Util\JSON;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use League\Tactician\CommandBus;
use Throwable;

/**
 * Class JobConsumer.
 *
 * @category Infrastructure\Event\Consumer
 * @SuppressWarnings(PHPMD)
 */
class JobCommandBusProcessor implements Processor, CommandSubscriberInterface, JobConsumerInterface
{
    use LoggerTrait;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var CommandFactoryInterface
     */
    private $commandFactory;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * Boolean result for job execution.
     *
     * @var bool
     */
    private $jobResult = false;

    /**
     * JobProgramConsumer constructor.
     *
     * @param JobRunner               $jobRunner
     * @param CommandBus              $commandBus
     * @param CommandFactoryInterface $commandFactory
     * @param EventDispatcher         $eventDispatcher
     */
    public function __construct(
        JobRunner $jobRunner,
        CommandBus $commandBus,
        CommandFactoryInterface $commandFactory,
        EventDispatcher $eventDispatcher
    ) {
        $this->jobRunner = $jobRunner;
        $this->commandBus = $commandBus;
        $this->commandFactory = $commandFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Process enqueue message.
     *
     * @param Message $message
     * @param Context $context
     *
     * @return object|string
     *
     * @throws Throwable
     */
    public function process(Message $message, Context $context)
    {
        /** @var CommandInterface $command */
        [$type, $command] = $this->makeCommand($message);
        // Build unique job name
        $name = $type.'_'.$command->getUuid()->toString();
        $ownerId = $message->getMessageId() ?? $command->getUuid()->toString();

        $this->eventDispatcher->dispatch(
            JobCommandBusEventListenerInterface::EVENT_PRE_PROCESS,
            [$message, $command]
        );

        $this->jobRunner->runUnique(
            $ownerId,
            $name,
            $this->getTaskCallback($type, $command)
        );

        if (!$this->isFinishSuccessfully()) {
            $this->eventDispatcher->dispatch(
                JobCommandBusEventListenerInterface::EVENT_FAILED_PROCESS,
                [$message, $command]
            );

            return self::REJECT;
        }

        $this->eventDispatcher->dispatch(
            JobCommandBusEventListenerInterface::EVENT_POST_PROCESS,
            [$message, $command]
        );

        return self::ACK;
    }

    /**
     * Build and return task callback.
     *
     * @param string           $type
     * @param CommandInterface $command
     *
     * @return Closure
     */
    private function getTaskCallback(string $type, CommandInterface $command): Closure
    {
        return function (JobRunner $runner, Job $job) use ($command): bool {
            try {
                $this->commandBus->handle($command);
                $this->setResult(true);

                return true;
            } catch (Throwable $e) {
                $this->logMessage($e->getMessage(), LOG_INFO);
                $this->setResult(false);

                return false;
            }
        };
    }

    /**
     * Is last job finish successfully.
     *
     * @return bool
     */
    private function isFinishSuccessfully(): bool
    {
        return $this->jobResult;
    }

    /**
     * Set job task final boolean result.
     *
     * @param bool $result
     *
     * @return $this
     */
    private function setResult(bool $result): self
    {
        $this->jobResult = $result;

        return $this;
    }

    /**
     * Make CommandBus command.
     *
     * @param Message $message
     *
     * @return mixed[]
     *
     * @throws AssertionFailedException
     */
    private function makeCommand(Message $message): array
    {
        $data = JSON::decode($message->getBody());
        Assertion::keyExists($data, 'type');
        Assertion::keyExists($data, 'args');
        $commandType = $data['type'];
        $args = $data['args'];

        if (!is_array($args)) {
            $args = [$args];
        }
        /** @psalm-suppress TooManyArguments */
        $command = $this->commandFactory->makeCommandInstanceByType($commandType, ...$args);

        return [$commandType, $command];
    }

    /**
     * Return enqueue command routers.
     *
     * @return string
     */
    public static function getSubscribedCommand(): string
    {
        return static::getRoute();
    }

    /**
     * Return enqueue route.
     *
     * @return string
     */
    public static function getRoute(): string
    {
        return 'task.command.run';
    }
}
