<?php

namespace Emdrive\Command\Service;

use Emdrive\Command\ScheduledCommandInterface;
use Emdrive\Service\CommandRunnerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class StopCommand
 * @package Emdrive\Command\Service
 */
class StopCommand extends Command
{
    /**
     * @var CommandRunnerService
     */
    private $commandRunnerService;

    const SUCCESSFULLY_EXECUTED = 0;

    /**
     * @required
     */
    public function setCommandRunnerService(CommandRunnerService $commandRunnerService): void
    {
        $this->commandRunnerService = $commandRunnerService;
    }

    protected function configure()
    {
        $this
            ->setName('emdrive:service:stop')
            ->setDescription('Stop service')
            ->addOption(
                'stop-all',
                null,
                InputOption::VALUE_NONE,
                'Stop all running scheduled commands'
            )
        ;

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
       $stopAll = $input->getOption('stop-all') ?: false;

        $this->commandRunnerService->setAvailableCommands(
            $this->getCommands($stopAll)
        );
        $this->commandRunnerService->stopRunningCommands();

        $output->writeln('Service - STOPPED');

        return self::SUCCESSFULLY_EXECUTED;
    }

    private function getCommands(bool $getAll = false): array
    {
        $application = $this->getApplication();
        $commands = [$application->get(RunCommand::COMMAND_NAME)];

        if ($getAll) {
            foreach ($application->all() as $command) {
                if ($command instanceof ScheduledCommandInterface) {
                    $commands[] = $command;
                }
            }
        }

        return $commands;
    }
}
