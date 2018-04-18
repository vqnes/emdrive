<?php

namespace Emdrive\EventListener;

use Emdrive\Command\ScheduledCommandInterface;
use Emdrive\Command\Service\RunCommand;
use Emdrive\InterruptableExecutionTrait;
use Emdrive\Service\PidService;
use Emdrive\Service\ScheduleService;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ScheduleCommandSubscriber implements EventSubscriberInterface
{
    use InterruptableExecutionTrait;

    /**
     * @var ScheduleService
     */
    private $schedule;

    /**
     * @var PidService
     */
    private $pidService;

    /**
     * @required
     * @param ScheduleService $schedule
     */
    public function setSchedule(ScheduleService $schedule)
    {
        $this->schedule = $schedule;
    }

    /**
     * @required
     * @param PidService $schedule
     */
    public function setPidService(PidService $pidService)
    {
        $this->pidService = $pidService;
    }

    private $isError = false;
    private $isComplete = false;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND      => ['onStart', 10],
            ConsoleEvents::ERROR        => ['onError', 10],
            ConsoleEvents::TERMINATE    => ['onComplete', 10],
        ];
    }

    public function onStart(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();

        if (($command instanceof ScheduledCommandInterface || $command instanceof RunCommand) && $event->commandShouldRun()) {
            $this->initInterruptHandler();
            $this->pidService->save($command);
            $this->schedule->setRunning($command->getName());

            register_shutdown_function(function () use ($command) {
                if (error_get_last()) {
                    $this->complete($command);
                }
            });
        }
    }

    public function onError(ConsoleErrorEvent $event)
    {
        $this->isError = true;
    }

    public function onComplete(ConsoleTerminateEvent $event)
    {
        $command = $event->getCommand();
        if ($command instanceof ScheduledCommandInterface || $command instanceof RunCommand) {
            $this->complete($command);
        }
    }

    public function complete($command)
    {
        if (!$this->isComplete) {
            $this->pidService->remove($command);
            $this->schedule->setStopped($command->getName(), $this->isError, $this->isInterrupted());
            //sleep(2);

            $this->isComplete = true;
        }
    }
}
