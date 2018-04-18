<?php

namespace Emdrive\EventListener;

use Emdrive\Command\LockableCommandInterface;
use Emdrive\LoggerAwareTrait;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LockableSubscriber implements EventSubscriberInterface
{
    use LoggerAwareTrait;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND      => ['onStart', 20],
            ConsoleEvents::TERMINATE    => ['onComplete', 20],
        ];
    }

    public function onStart(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();

        if ($command instanceof LockableCommandInterface) {
            $input = $event->getInput();

            $blocking = $input->hasOption('wait-and-execute') ? $input->getOption('wait-and-execute') : false;

            if (!$command->lock($blocking)) {
                $this->logger->error($command->getName() . ' is already running');
                $event->disableCommand();
            }
        }
    }

    public function onComplete(ConsoleTerminateEvent $event)
    {
        $command = $event->getCommand();
        if ($command instanceof LockableCommandInterface) {
            $command->unlock();
        }
    }
}
