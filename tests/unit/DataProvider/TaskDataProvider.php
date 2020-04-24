<?php

declare(strict_types=1);

namespace MicroModule\Task\Tests\Unit\DataProvider;

/**
 * Class TaskDataProvider.
 *
 * @category Tests\Unit\DataProvider
 */
class TaskDataProvider
{
    /**
     * Return error data fixture.
     *
     * @return mixed[]
     *
     * @throws \Exception
     */
    public function getData(): array
    {
        return [
            [
                json_encode(['type' => 'ProgramCollectionRunCommand', 'args' => ['72a541ba-4bb4-454f-9ed5-3dcfe6ca9f2e']]),
                '72a541ba-4bb4-454f-9ed5-3dcfe6ca9f2e',
            ],
        ];
    }
}
