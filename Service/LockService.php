<?php

namespace Emdrive\Service;

use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Lock\Store\FlockStore;

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

    /**
     * @param string|null $name
     * @return string|null
     */
    public function getLockFileFullPath(?string $name): ?string
    {
        $lockFileFullPath = null;

        if ($lock = $this->locks[$name]) {
            if ($key = $this->getPrivateKeyFromLock($lock)) {
                $resource = $key->getState(FlockStore::class);
                $lockFileFullPath = stream_get_meta_data($resource)["uri"];
            }
        }

        return $lockFileFullPath;
    }

    /**
     * @param Lock $lock
     * @return Key|null
     */
    private function getPrivateKeyFromLock(Lock $lock): ?Key
    {
        $key = null;

        if ($lock) {
            $reflectionProperty = new \ReflectionProperty(Lock::class, 'key');
            $reflectionProperty->setAccessible(true);
            $key = $reflectionProperty->getValue($lock);
        }

        return $key;
    }
}
