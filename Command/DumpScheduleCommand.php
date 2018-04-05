<?php

namespace Emdrive\Command;

use Emdrive\Service\ScheduleService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DumpScheduleCommand extends Command
{
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

    protected function configure()
    {
        $this
            ->setName('emdrive:dump-schedule')
            ->setDescription(
                'Dump schedule into console'
            )
        ;

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->schedule->drawSchedule($output, $this->schedule->getAll());
    }
}
