<?php

namespace Emdrive\Service;

use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Lock;

class LockService
{
    /**
     * @var LockFactory
     */
    private $factory;

    /**
     * @var Lock[]
     */
    private $locks = [];

    public function __construct(LockFactory $factory)
    {
        $this->factory = $factory;
    }

    public function isLocked($name)
    {
        if (isset($this->locks[$name]) && $this->locks[$name]->isAcquired()) {
            return true;
        }
        if (!$this->lock($name)) {
            return true;
        }
        $this->unlock($name);
        return false;
    }

    public function lock($name, $blocking = false, $ttl = 0, $autoRelease = false)
    {
        $lock = $this->factory->createLock($name, $ttl, $autoRelease);

        if ($lock->acquire($blocking)) {
            $this->locks[$name] = $lock;
            return true;
        }
        return false;
    }

    public function unlock($name)
    {
        if (isset($this->locks[$name])) {
            $result = $this->locks[$name]->release();
            unset($this->locks[$name]);
            return $result;
        }
    }
}
