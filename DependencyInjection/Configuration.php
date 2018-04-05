<?php

namespace Emdrive\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('emdrive');

        $rootNode
            ->children()
                ->arrayNode('storage')
                    ->children()
                        ->scalarNode('dsn')
                            ->isRequired()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('lock')
                    ->isRequired()
                    ->defaultValue('flock')
                ->end()
                ->scalarNode('pid_dir')
                    ->isRequired()
                    ->defaultValue('var/pid')
                ->end()
                ->scalarNode('log_dir')
                    ->isRequired()
                    ->defaultValue('var/log')
                ->end()
                ->scalarNode('lock_dir')
                    ->isRequired()
                    ->defaultValue('var/lock')
                ->end()
                ->scalarNode('server_name')
                    ->defaultValue('emdrive 1')
                    ->isRequired()
                ->end()
                ->integerNode('pool_size')
                    ->min(1)
                    ->max(100)
                    ->defaultValue(5)
                    ->isRequired()
                ->end()
                ->integerNode('tick_interval')
                    ->isRequired()
                    ->min(1000000)
                    ->defaultValue(2000000)
                ->end()
                ->scalarNode('cmd_start')
                    ->defaultValue('bin/console %s -vv -e prod > /dev/null 2>&1 &')
                    ->isRequired()
                ->end()
                ->scalarNode('cmd_kill')
                    ->defaultValue('kill -2 %s -vv -e prod > /dev/null 2>&1 &')
                    ->isRequired()
                ->end()
            ->end();

        return $treeBuilder;
    }
}