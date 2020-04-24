<?php

declare(strict_types=1);

namespace MicroModule\Task\Application\Processor;

use MicroModule\Base\Utils\LoggerTrait;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Throwable;

/**
 * Class JobEventConsumer.
 *
 * @category Domain\Event\Consumer
 * @SuppressWarnings(PHPMD)
 */
class JobEventProcessor implements Processor, TopicSubscriberInterface
{
    use LoggerTrait;

    public const SUBSCRIBED_TASK_EVENT_COMMAND = 'task.event';

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
        $messageBody = $message->getBody();
        $this->logMessage('Consume job event', LOG_DEBUG);

        try {
            $messageBody = json_decode($messageBody, true);
        } catch (Throwable $exception) {
            $this->logMessage('Consume job event with Exception', LOG_DEBUG);
        }

        return self::ACK;
    }

    /**
     * Return enqueue command routers.
     *
     * @return string
     */
    public static function getSubscribedTopics(): string
    {
        return self::SUBSCRIBED_TASK_EVENT_COMMAND;
    }
}
