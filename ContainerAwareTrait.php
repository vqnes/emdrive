<?php

namespace Emdrive;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;

/**
 * Trait ContainerAwareTrait
 * @package Emdrive
 */
trait ContainerAwareTrait
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @required
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
