<?php

namespace Emdrive\DependencyInjection;

use Emdrive\Lock\CombinedStoreFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class LockPass
 * @package Emdrive\DependencyInjection
 */
class LockPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $storeDefinition = $container->findDefinition('lock.store.combined.abstract');
        //$storeDefinition->setClass(CombinedStore::class);
        $storeDefinition->setFactory([CombinedStoreFactory::class, 'createStore']);
        $storeDefinition->setArgument(1, new Reference(Config::class));
    }
}
