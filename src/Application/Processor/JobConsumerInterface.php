<?php

declare(strict_types=1);

namespace MicroModule\Task\Application\Processor;

/**
 * Interface JobConsumerInterface.
 */
interface JobConsumerInterface
{
    /**
     * @return string
     */
    public static function getRoute(): string;
}
