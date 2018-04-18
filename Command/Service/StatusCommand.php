<?php

namespace Emdrive\Command\Service;

use Emdrive\InterruptableExecutionTrait;
use Emdrive\Service\PidService;
use Emdrive\Service\ScheduleService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class StatusCommand
 *
 * @package Emdrive\Command\Service
 */
class StatusCommand extends Command
{
    use InterruptableExecutionTrait;

    /**
     * @var ScheduleService
     */
    private $schedule;

    /**
     * @required
     * @param ScheduleService $schedule
     */
    public function setSchedule(ScheduleService $schedule)
    {
        $this->schedule = $schedule;
    }

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
            ->setName('emdrive:service:status')
            ->addOption('watch', 'w', InputOption::VALUE_NONE, 'Show processed status in realtime')
            ->setDescription(
                'Service status'
            )
        ;

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var RunCommand $runCommand */
        $runCommand = $this->getApplication()->get('emdrive:service:run');

        $output->write('Service ');

        if ($pid = $this->pidService->getPid($runCommand)) {
            $output->writeln(sprintf('[%s] - RUNNING', $pid));
        } else {
            $output->writeln('- STOPPED');
        }
        if ($input->getOption('watch')) {
            $this->watch($input, $output);
        }
    }

    private function watch(InputInterface $input, OutputInterface $output)
    {
        $isFirst = true;
        while (!$this->isInterrupted()) {
            $schedule = $this->schedule->getAll();

            if ($isFirst) {
                $isFirst = false;
            } else {
                $output->write(str_repeat("\x1B[1A\x1B[2K", count($schedule) + 4));
            }

            $this->schedule->drawSchedule($output, $schedule);

            sleep(1);
        }
    }
}
