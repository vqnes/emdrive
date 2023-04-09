<?php

namespace Emdrive\Service;

use Emdrive\DependencyInjection\Config;
use Emdrive\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;

class CommandRunnerService
{
    use LoggerAwareTrait;

    /**
     * @var PidService
     */
    protected $pidService;

    /**
     * @var ScheduleService
     */
    protected $schedule;

    /**
     * @var int
     */
    private $poolSize;

    /**
     * @var string
     */
    private $cmdStart;

    /**
     * @var string
     */
    private $cmdKill;

    /**
     * @var array<Command>
     */
    protected $availableCommands = [];

    public function __construct(Config $config, PidService $pidService, ScheduleService $schedule)
    {
        $this->pidService = $pidService;
        $this->schedule = $schedule;

        $this
            ->setPoolSize($config->poolSize)
            ->setCmdStart($config->cmdStart)
            ->setCmdKill($config->cmdKill);
    }

    /**
     * @return string[]
     */
    protected function getLockedCommands(): array
    {
        $commands = [];
        foreach ($this->availableCommands as $command) {
            if ($command->isLockedExecution()) {
                $commands[] = $command->getName();
            }
        }

        return $commands;
    }

    /**
     * @return array<Command>
     */
    protected function getRunningCommands(): array
    {
        $commands = [];
        foreach ($this->availableCommands as $command) {
            if ($this->pidService->getPid($command)) {
                $commands[] = $command;
            }
        }

        return $commands;
    }

    public function runCommandsReadyToBeStarted(): void
    {
        $availableSlots = $this->poolSize - count($this->getRunningCommands());

        if ($availableSlots > 0) {
            $lockedCommands = $this->getLockedCommands();
            if ($commandList = $this->schedule->findJobsToBeStarted($availableSlots, $lockedCommands)) {
                foreach ($commandList as $command) {
                    $this->exec($this->cmdStart, $command);
                }
            }
        }
    }

    public function stopRunningCommands(): void
    {
        $this->logger->info('Stopping jobs');

        foreach ($this->getRunningCommands() as $command) {
            if ($pid = $this->pidService->getPid($command)) {
                $this->exec($this->cmdKill, $pid);
            }
        }

        $this->waitRunningCommands();

        $this->logger->info('Jobs have been stopped');
    }

    protected function waitRunningCommands(): void
    {
        $this->logger->info('Waiting for job interruption...');

        $runningCommands = $this->getRunningCommands();
        $runningCommandsCount = count($runningCommands);
        $lastRunningCommandsCount = $runningCommandsCount + 1;

        while ($runningCommandsCount > 0) {
            if ($lastRunningCommandsCount > $runningCommandsCount) {
                $this->logger->info(sprintf(
                    'Waiting for %d job(-s): [%s]',
                    $runningCommandsCount,
                    implode(', ', $this->getCommandNames($runningCommands))
                ));
            }

            $lastRunningCommandsCount = $runningCommandsCount;
            $runningCommands = $this->getRunningCommands();
            $runningCommandsCount = count($runningCommands);

            sleep(1);
        }
    }

    /**
     * @param array<Command> $commands
     *
     * @return array<string>
     */
    protected function getCommandNames(array $commands): array
    {
        return array_map(function (Command $command) {
            return $command->getName();
        }, $commands);
    }

    /**
     * @param string $cmdTemplate
     * @param mixed  $argument
     *
     * @return void
     */
    protected function exec(string $cmdTemplate, $argument): void
    {
        $cmd = sprintf($cmdTemplate, $argument);

        $this->logger->notice(sprintf('Exec: <info>%s</info>', $cmd));

        exec($cmd);
    }

    /**
     * @param array<Command> $availableCommands
     *
     * @return $this
     */
    public function setAvailableCommands(array $availableCommands): self
    {
        $this->availableCommands = $availableCommands;

        return $this;
    }

    /**
     * @return array<Command>
     */
    public function getAvailableCommands(): array
    {
        return $this->availableCommands;
    }

    public function setPoolSize(int $poolSize): self
    {
        $this->poolSize = $poolSize;

        return $this;
    }

    public function getPoolSize(): int
    {
        return $this->poolSize;
    }

    public function setCmdStart(string $cmdStart): self
    {
        $this->cmdStart = $cmdStart;

        return $this;
    }

    public function setCmdKill(string $cmdKill): self
    {
        $this->cmdKill = $cmdKill;

        return $this;
    }
}
