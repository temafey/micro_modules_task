<?php

declare(strict_types=1);

namespace MicroModule\Task\Tests\Unit\Application\Processor;

use MicroModule\Base\Application\Command\CommandInterface;
use MicroModule\Base\Application\Factory\CommandFactoryInterface;
use MicroModule\Task\Application\Processor\JobCommandBusProcessor;
use MicroModule\Task\Infrastructure\Service\Test\JobRunner;
use Broadway\EventDispatcher\Testing\TraceableEventDispatcher;
use Enqueue\Client\CommandSubscriberInterface;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use League\Tactician\CommandBus;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;
use Throwable;

/**
 * Class JobCommandBusProcessorTest.
 *
 * @category Tests\Unit\Infrastructure\Event\Consumer
 */
class JobCommandBusProcessorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Broadway EventDispatcher.
     *
     * @var TraceableEventDispatcher
     */
    private $traceableEventDispatcher;

    /**
     * @test
     *
     * @group unit
     *
     * @dataProvider \MicroModule\Task\Tests\Unit\DataProvider\TaskDataProvider::getData
     *
     * @param string $taskCommand
     * @param string $uuid
     *
     * @throws Throwable
     */
    public function processSuccessTest(string $taskCommand, string $uuid): void
    {
        $testJobRunner = new JobRunner(Mockery::mock(\Enqueue\JobQueue\JobProcessor::class));
        /** @var CommandBus $commandBusMock */
        $commandBusMock = $this->makeCommandBusMock(1, false);

        $uuidMock = $this->makeUuidMock($uuid, 1);
        $commandMock = $this->makeCommandMock($uuidMock, 1);
        $commandFactoryMock = $this->makeCommandFactoryMock($commandMock);
        $this->traceableEventDispatcher = new TraceableEventDispatcher();

        $jobProgramConsumer = new JobCommandBusProcessor($testJobRunner, $commandBusMock, $commandFactoryMock, $this->traceableEventDispatcher);
        self::assertInstanceOf(Processor::class, $jobProgramConsumer);
        self::assertInstanceOf(CommandSubscriberInterface::class, $jobProgramConsumer);

        $loggerMock = $this->makeLoggerMock(0);
        $jobProgramConsumer->setLogger($loggerMock);
        $messageMock = $this->makeMessageMock($uuid, $taskCommand, 3);
        /** @var Context $contextMock */
        $contextMock = Mockery::mock(Context::class);

        self::assertSame(Processor::ACK, $jobProgramConsumer->process($messageMock, $contextMock));

        $dispatchedEvents = $this->traceableEventDispatcher->getDispatchedEvents();
        $this->assertCount(2, $dispatchedEvents);
        $this->assertEquals('enqueue.job.pre_process', $dispatchedEvents[0]['event']);
        $this->assertInstanceOf(Message::class, $dispatchedEvents[0]['arguments'][0]);
        $this->assertInstanceOf(CommandInterface::class, $dispatchedEvents[0]['arguments'][1]);
        $this->assertEquals($uuid, $dispatchedEvents[0]['arguments'][0]->getMessageId());
        $this->assertEquals($taskCommand, $dispatchedEvents[0]['arguments'][0]->getBody());

        $this->assertEquals('enqueue.job.post_process', $dispatchedEvents[1]['event']);
        $this->assertInstanceOf(Message::class, $dispatchedEvents[0]['arguments'][0]);
        $this->assertInstanceOf(CommandInterface::class, $dispatchedEvents[0]['arguments'][1]);
        $this->assertEquals($uuid, $dispatchedEvents[0]['arguments'][0]->getMessageId());
        $this->assertEquals($taskCommand, $dispatchedEvents[0]['arguments'][0]->getBody());
    }

    /**
     * @test
     *
     * @group unit
     *
     * @dataProvider \MicroModule\Task\Tests\Unit\DataProvider\TaskDataProvider::getData
     *
     * @param string $taskCommand
     * @param string $uuid
     *
     * @throws Throwable
     */
    public function processFailedTest(string $taskCommand, string $uuid): void
    {
        $testJobRunner = new JobRunner(Mockery::mock(\Enqueue\JobQueue\JobProcessor::class));
        /** @var CommandBus $commandBusMock */
        $commandBusMock = $this->makeCommandBusMock(1, true);

        $uuidMock = $this->makeUuidMock($uuid, 1);
        $commandMock = $this->makeCommandMock($uuidMock, 1);
        $commandFactoryMock = $this->makeCommandFactoryMock($commandMock);
        $this->traceableEventDispatcher = new TraceableEventDispatcher();

        $jobProgramConsumer = new JobCommandBusProcessor($testJobRunner, $commandBusMock, $commandFactoryMock, $this->traceableEventDispatcher);
        self::assertInstanceOf(Processor::class, $jobProgramConsumer);
        self::assertInstanceOf(CommandSubscriberInterface::class, $jobProgramConsumer);

        $loggerMock = $this->makeLoggerMock(1);
        $jobProgramConsumer->setLogger($loggerMock);
        $messageMock = $this->makeMessageMock($uuid, $taskCommand, 3);
        /** @var Context $contextMock */
        $contextMock = Mockery::mock(Context::class);

        self::assertSame(Processor::REJECT, $jobProgramConsumer->process($messageMock, $contextMock));

        $dispatchedEvents = $this->traceableEventDispatcher->getDispatchedEvents();
        $this->assertCount(2, $dispatchedEvents);

        $this->assertEquals('enqueue.job.pre_process', $dispatchedEvents[0]['event']);
        $this->assertInstanceOf(Message::class, $dispatchedEvents[0]['arguments'][0]);
        $this->assertInstanceOf(CommandInterface::class, $dispatchedEvents[0]['arguments'][1]);
        $this->assertEquals($uuid, $dispatchedEvents[0]['arguments'][0]->getMessageId());
        $this->assertEquals($taskCommand, $dispatchedEvents[0]['arguments'][0]->getBody());

        $this->assertEquals('enqueue.job.failed_process', $dispatchedEvents[1]['event']);
        $this->assertInstanceOf(Message::class, $dispatchedEvents[0]['arguments'][0]);
        $this->assertInstanceOf(CommandInterface::class, $dispatchedEvents[0]['arguments'][1]);
        $this->assertEquals($uuid, $dispatchedEvents[0]['arguments'][0]->getMessageId());
        $this->assertEquals($taskCommand, $dispatchedEvents[0]['arguments'][0]->getBody());
    }

    /**
     * Return Logger mock object.
     *
     * @param int  $times
     * @param bool $throwException
     *
     * @return MockInterface
     */
    protected function makeCommandBusMock(int $times = 1, bool $throwException = false): MockInterface
    {
        $commandBusMock = Mockery::mock(CommandBus::class);
        $handleMethod = $commandBusMock
            ->shouldReceive('handle')
            ->times($times)
            ->andReturn('');

        if ($throwException) {
            $handleMethod->andThrow(Mockery\Exception::class, 'Test exception');
        }

        return $commandBusMock;
    }

    /**
     * Make and return uuid mock.
     *
     * @param string $uuid
     * @param int    $times
     *
     * @return MockInterface
     */
    private function makeUuidMock(string $uuid, int $times = 1): MockInterface
    {
        $uuidMock = Mockery::mock(UuidInterface::class);
        $uuidMock
            ->shouldReceive('toString')
            ->times($times)
            ->andReturn($uuid);

        return $uuidMock;
    }

    /**
     * Make and return Command mock.
     *
     * @param MockInterface $uuidMock
     * @param int           $times
     *
     * @return MockInterface
     */
    private function makeCommandMock(MockInterface $uuidMock, int $times = 1): MockInterface
    {
        $commandMock = Mockery::mock(CommandInterface::class);
        $commandMock
            ->shouldReceive('getUuid')
            ->times($times)
            ->andReturn($uuidMock);

        return $commandMock;
    }

    /**
     * Make and return CommandFactory mock.
     *
     * @param MockInterface $commandMock
     * @param int           $times
     *
     * @return MockInterface
     */
    private function makeCommandFactoryMock(MockInterface $commandMock, int $times = 1): MockInterface
    {
        $commandFactoryMock = Mockery::mock(CommandFactoryInterface::class);
        $commandFactoryMock
            ->shouldReceive('makeCommandInstanceByType')
            ->times($times)
            ->andReturn($commandMock);

        return $commandFactoryMock;
    }

    /**
     * Return Logger mock object.
     *
     * @param int $times
     *
     * @return MockInterface
     */
    protected function makeLoggerMock(int $times = 1): MockInterface
    {
        $loggerMock = Mockery::mock(LoggerInterface::class);
        $loggerMock
            ->shouldReceive('info')
            ->times($times)
            ->andReturn('');

        return $loggerMock;
    }

    /**
     * Make and return Message mock.
     *
     * @param string $uuid
     * @param string $taskCommand
     * @param int    $times
     *
     * @return MockInterface
     */
    private function makeMessageMock(string $uuid, string $taskCommand, int $times = 1): MockInterface
    {
        $messageMock = Mockery::mock(Message::class);
        $messageMock
            ->shouldReceive('getMessageId')
            ->times($times)
            ->andReturn($uuid);
        $messageMock
            ->shouldReceive('getBody')
            ->times($times)
            ->andReturn($taskCommand);

        return $messageMock;
    }
}
