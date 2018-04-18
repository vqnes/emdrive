<?php

namespace Emdrive\Command;

/**
 * Trait DeployedCommandTrait
 *
 * @package Emdrive\Command
 */
trait DeployedCommandTrait
{
    public function getDeployPriority()
    {
        return 0;
    }
}