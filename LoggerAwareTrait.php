<?php

namespace Emdrive;

use Monolog\Logger;
use Psr\Log\LoggerInterface;

trait LoggerAwareTrait
{
    /**
     * The logger instance.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * @required
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}