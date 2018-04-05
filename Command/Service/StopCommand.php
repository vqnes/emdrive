<?php

namespace Emdrive\Command\Service;

use Emdrive\Service\PidService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class StopCommand
 * @package Emdrive\Command\Service
 */
class StopCommand extends Command
{
    /**
     * @var PidService
     */
    private $pidService;

    /**
     * @required
     * @param PidService $pidService
     */
    public function setPidService(PidService $pidService)
    {
        $this->pidService = $pidService;
    }

    protected function configure()
    {
        $this
            ->setName('emdrive:service:stop')
            ->setDescription(
                'Stop service'
            )
        ;

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $runCommand = $this->getApplication()->get('emdrive:service:run');

        if ($pid = $this->pidService->getPid($runCommand)) {
            exec('kill -2 ' . $pid);
        }
        $output->writeln('Service - STOPPED');
    }
}
