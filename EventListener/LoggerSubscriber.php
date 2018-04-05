<?php

namespace Emdrive\EventListener;

use Emdrive\InterruptableExecutionTrait;
use Emdrive\LoggerAwareTrait;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LoggerSubscriber implements EventSubscriberInterface
{
    use LoggerAwareTrait;
    use InterruptableExecutionTrait;

    private $startedAt;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND      => ['onStart', 0],
            ConsoleEvents::TERMINATE    => ['onComplete', 0],
        ];
    }

    public function onStart(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();

        if ($this->isLoggedCommand($command)) {
            $this->initInterruptHandler();

            $this->startedAt = time();

            $this->logger->notice(sprintf('<info>%s</info> started - %s', $command->getName(), \getmypid()));
        }
    }

    public function onComplete(ConsoleTerminateEvent $event)
    {
        if ($this->startedAt) {
            if ($this->isInterrupted()) {
                $this->logger->warning(sprintf(
                    '<info>%s</info> was interrupted after <info>%s</info>s',
                    $event->getCommand()->getName(),
                    (time() - $this->startedAt)
                ));
            } else {
                $this->logger->notice(sprintf(
                    '<info>%s</info> finished in <info>%s</info>s',
                    $event->getCommand()->getName(),
                    (time() - $this->startedAt)
                ));
            }
        }
    }

    private function isLoggedCommand($command)
    {
        return method_exists($command, 'setLogger');
    }
}
