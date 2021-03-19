<?php

namespace Emdrive;

use Emdrive\Service\LockService;
use Symfony\Component\Console\Command\Command;

/**
 * Trait LockableExecutionTrait
 * @package Emdrive
 */
trait LockableExecutionTrait
{
    /**
     * @var LockService
     */
    protected $lockService;

    /**
     * @required
     * @param LockService $lockService
     */
    public function setLockService(LockService $lockService)
    {
        $this->lockService = $lockService;
    }

    public function getLockName()
    {
        if ($this instanceof Command) {
            return $this->getName();
        } else {
            return static::class;
        }
    }

    public function lock($blocking = false)
    {
        $lockName = $this->getLockName();

        if ($this->lockService->lock($lockName, $blocking)) {
            register_shutdown_function(function () {
                if (error_get_last()) {
                    $this->unlock();
                }
            });
            return true;
        }
        return false;
    }

    public function isLockedExecution()
    {
        return $this->lockService->isLocked($this->getLockName());
    }

    public function unlock()
    {
        $this->lockService->unlock($this->getLockName());
    }
}
