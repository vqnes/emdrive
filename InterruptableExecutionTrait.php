<?php

namespace Emdrive;

/**
 * Trait InterruptableExecutionTrait
 * @package Emdrive
 */
trait InterruptableExecutionTrait
{
    private $isInterrupted = false;
    private $isInterruptHandlerInitialized = false;

    public function initInterruptHandler()
    {
        if (!$this->isInterruptHandlerInitialized) {
            foreach ([\SIGINT, \SIGTERM, \SIGHUP] as $signo) {
                $oldHandler = pcntl_signal_get_handler($signo);
                $handler = function ($signo) use ($oldHandler) {
                    $this->setInterrupted();

                    if ($oldHandler) {
                        call_user_func($oldHandler, $signo);
                    }
                };
                pcntl_signal($signo, $handler);
            }

            $this->isInterruptHandlerInitialized = true;
        }
    }

    public function isInterrupted()
    {
        if (!$this->isInterrupted) {
            $this->initInterruptHandler();
            pcntl_signal_dispatch();
        }

        return $this->isInterrupted;
    }

    public function setInterrupted($interrupted = true)
    {
        $this->isInterrupted = $interrupted;
    }
}