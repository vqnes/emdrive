<?php

namespace Emdrive\Lock;

use Emdrive\DependencyInjection\Config;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Strategy\UnanimousStrategy;

class CombinedStoreFactory
{
    public static function createStore(array $stores, Config $config)
    {
        foreach ($stores as $i => $store) {
            if ($store instanceof FlockStore) {
                $stores[$i] = new FlockStore($config->lockDir);
            }
        }
        return new \Symfony\Component\Lock\Store\CombinedStore($stores, new UnanimousStrategy());
    }
}
