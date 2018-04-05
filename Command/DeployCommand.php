<?php

namespace Emdrive\Command;

use Emdrive\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Deploy Command
 */
class DeployCommand extends Command
{
    use LoggerAwareTrait;

    protected function configure()
    {
        $this
            ->setName('emdrive:deploy')
            ->setDescription(
                'Deploy'
            )
         ;

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commands = [];
        foreach ($this->getApplication()->all() as $command) {
            if ($command instanceof DeploymentCommandIterface) {
                if ($this->allowToDeploy($command)) {
                    $commands[$command->getName()] = $command->getDeployPriority();
                }
            }
        }

        asort($commands);

        /** @var $command DeploymentCommandIterface */
        foreach (array_keys($commands) as $commandName) {
            $command = $this->getApplication()->get($commandName);

            $input = new ArrayInput([]);
            $command->run($input, $output);

            if ($command->deployOnce()) {

            }
        }
    }

    public function allowToDeploy(DeploymentCommandIterface $command)
    {
        return !$command->deployOnce() || true;
    }
}
