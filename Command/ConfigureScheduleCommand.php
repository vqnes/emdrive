<?php

namespace Emdrive\Command;

use Emdrive\Service\ScheduleService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class ConfigureScheduleCommand extends Command
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
            ->setName('emdrive:configure-schedule')
            ->addArgument('name', InputArgument::OPTIONAL, 'Command name')
            ->setDescription(
                'Configure schedule'
            )
        ;

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->getApplication()->all() as $command) {
            if ($command instanceof ScheduledCommandInterface) {
                if (!$input->getArgument('name') || $input->getArgument('name') === $command->getName()) {
                    $this->editSchedule($command, $input, $output);
                }
            }
        }
    }

    protected function editSchedule(Command $command, InputInterface $input, OutputInterface $output)
    {
        $row = $this->schedule->findByName($command->getName());

        $output->writeln(sprintf('<info>%s</info> runs on <comment>%s</comment> <comment>%s</comment>', $row['name'], $row['schedule_type'], $row['schedule_value']));

        $question = new SymfonyQuestionHelper();

        if (!$question->ask($input, $output, new ConfirmationQuestion('Do you want to change schedule for this command?', false))) {
            return;
        }

        $scheduleType = $question->ask($input, $output, new ChoiceQuestion('Enter schedule type', ['interval', 'time'], 0));

        if ('interval' == $scheduleType) {
            do {
                $scheduleValue = $question->ask($input, $output, new Question('Enter interval', '1 hour'));
                if (!preg_match('/[1-9][0-9]*\s(hour|minute|day)s{0,1}/i', $scheduleValue)) {
                    $output->writeln(sprintf(
                        'Invalid value <error>%s</error> enter <comment>X [hour|minute|day]</comment> ',
                        $scheduleValue
                    ));
                } else {
                    break;
                }
            } while (true);
        } else {

            do {
                $scheduleValue = $question->ask($input, $output, new Question('Enter time of the day', '12:30'));

                if (!preg_match('/\d{2}\:\d{2}/', $scheduleValue)) {
                    $output->writeln(
                        sprintf('Invalid value <error>%s</error> enter <comment>XX:XX</comment>', $scheduleValue)
                    );
                } else {
                    break;
                }
            } while (true);
        }

        $this->schedule->updateJob($row['name'], ['schedule_type' => $scheduleType, 'schedule_value' => $scheduleValue]);
    }
}
