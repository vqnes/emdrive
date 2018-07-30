<?php

namespace Emdrive\Command\Deploy;

use Emdrive\Command\DeploymentCommandIterface;
use Emdrive\Command\ScheduledCommandInterface;
use Emdrive\LoggerAwareTrait;
use Emdrive\Service\ScheduleService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpdateScheduleCommand
 * @package Emdrive\Command\Deploy
 */
class UpdateScheduleCommand extends Command implements DeploymentCommandIterface
{
    use LoggerAwareTrait;

    public function getDeployPriority()
    {
        return 10000;
    }

    /**
     * @var ScheduleService
     */
    private $schedule;

    /**
     * @required
     */
    public function setSchedule(ScheduleService $schedule)
    {
        $this->schedule = $schedule;
    }

    protected function configure()
    {
        $this
            ->setName('emdrive:deploy:update-schedule')
            ->setDescription(
                'Update schedule'
            )
         ;

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->schedule->createTable();

        $this->logger->info('Updating schedule');

        $isUpdated = false;
        $now = date('Y-m-d H:i:s');

        $existingRecords = [];
        foreach ($this->schedule->getAll() as $row) {
            $existingRecords[$row['name']] = true;
        }

        foreach ($this->getApplication()->all() as $command) {
            if ($command instanceof ScheduledCommandInterface) {
                if (!isset($existingRecords[$command->getName()])) {
                    $this->logger->notice('+ ' . $command->getName());
                    $isUpdated = true;

                    $fields = [
                        'name'              => $command->getName(),
                        'status'            => ScheduleService::STATUS_STOPPED,
                        'last_start_at'     => $now,
                        'next_start_at'     => $now,
                        'schedule_type'     => ScheduleService::SCHEDULE_INTERVAL,
                        'schedule_value'    => $command->defaultInterval ?? '1 hour',
                    ];

                    $this->schedule->addJob($fields);
                } else {
                    unset($existingRecords[$command->getName()]);
                }
            }
        }

        foreach ($existingRecords as $name => $val) {
            $this->logger->notice('- ' . $name);
            $this->schedule->removeJob($name);
            $isUpdated = true;
        }

        if (!$isUpdated) {
            $this->logger->info('Schedule is up to date');
        }
    }
}
