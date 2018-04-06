<?php

namespace Emdrive\DependencyInjection;

use Emdrive\Service;
use Emdrive\Storage;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EmdriveExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $this->registerConfigConfiguration($config, $container);

        $this->registerStorageConfiguration($config['storage'], $container);

        $this->registerLockConfiguration($config['lock'], $container);
    }

    private function registerConfigConfiguration(array $config, ContainerBuilder $container)
    {
        $configDef = $container->findDefinition(Config::class);
        $configDef->setProperty('serverName', $config['server_name']);
        $configDef->setProperty('logDir', $config['log_dir']);
        $configDef->setProperty('lockDir', $config['lock_dir']);
        $configDef->setProperty('pidDir', $config['pid_dir']);
        $configDef->setProperty('poolSize', $config['pool_size']);
        $configDef->setProperty('cmdStart', $config['cmd_start']);
        $configDef->setProperty('cmdKill', $config['cmd_kill']);
        $configDef->setProperty('tickInterval', $config['tick_interval']);
    }

    private function registerStorageConfiguration(array $config, ContainerBuilder $container)
    {
        $storageDefinition = new Definition(Storage\StorageInterface::class);
        $storageDefinition->setFactory([Storage\StorageFactory::class, 'createStorage']);
        $storageDefinition->setArgument(0, $config['dsn']);

        $container->setDefinition(Storage\StorageInterface::class, $storageDefinition);
    }

    private function registerLockConfiguration($lockName, ContainerBuilder $container)
    {
        $container->findDefinition(Service\LockService::class)->setArguments([
            new Reference('lock.' . $lockName . '.factory')
        ]);
    }
}
