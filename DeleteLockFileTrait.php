<?php

namespace Emdrive;

use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Lock\Store\FlockStore;

/**
 * Trait DeleteLockFileTrait
 * @package Emdrive
 */
trait DeleteLockFileTrait
{
    /**
     * @param string|null $lockFileFullPath
     */
    protected function deleteLockFile(?string $lockFileFullPath)
    {
        if ($lockFileFullPath) {
            unlink($lockFileFullPath);
        }
    }

    /**
     * @param Lock|null $lock
     * @return string|null
     */
    protected function getLockFileFullPath(?Lock $lock): ?string
    {
        $lockFileFullPath = null;

        if ($lock) {
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
