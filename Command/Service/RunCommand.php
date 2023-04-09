<?php

namespace Emdrive\Command\Service;

use Emdrive\Command\LockableCommandInterface;
use Emdrive\Command\ScheduledCommandInterface;
use Emdrive\DependencyInjection\Config;
use Emdrive\InterruptableExecutionTrait;
use Emdrive\LockableExecutionTrait;
use Emdrive\LoggerAwareTrait;
use Emdrive\Service\CommandRunnerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RunCommand
 *
 * @package Emdrive\Command\Service
 */
class RunCommand extends Command implements LockableCommandInterface
{
    use LockableExecutionTrait;
    use InterruptableExecutionTrait;
    use LoggerAwareTrait;

    /**
     * @var CommandRunnerService
     */
    private $commandRunnerService;

    /**
     * @var Config
     */
    private $config;

    const COMMAND_NAME = 'emdrive:service:run';

    const SUCCESSFULLY_EXECUTED = 0;

    /**
     * @required
     *
     * @param Config $config
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @required
     */
    public function setCommandRunnerService(CommandRunnerService $commandRunnerService): void
    {
        $this->commandRunnerService = $commandRunnerService;
    }

    public function getLockName()
    {
        return $this->getName() . $this->config->serverName;
    }

    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription(
                'Start service'
            )
            ->addOption('poolSize', null, InputOption::VALUE_REQUIRED, 'Pool size')
            ->addOption('watch', 'w', InputOption::VALUE_NONE, 'Continuous monitoring and execution of commands');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $watch = $input->getOption('watch');
        $poolSize = $input->getOption('poolSize');

        if ($poolSize) {
            $this->commandRunnerService->setPoolSize($poolSize);
        }
        $this->commandRunnerService->setAvailableCommands(
            $this->getAvailableCommands()
        );

        $this->commandRunnerService->runCommandsReadyToBeStarted();
        if ($watch) {
            $this->watch();
        }

        return self::SUCCESSFULLY_EXECUTED;
    }

    private function getAvailableCommands()
    {
        foreach ($this->getApplication()->all() as $command) {
            if ($command instanceof ScheduledCommandInterface && $command !== $this) {
                $this->availableCommands[] = $command;
            }
        }

        return $this->availableCommands;
    }

    private function watch()
    {
        while (!$this->isInterrupted()) {
            $this->commandRunnerService->runCommandsReadyToBeStarted();
            usleep($this->config->tickInterval);
        }
    }
}
