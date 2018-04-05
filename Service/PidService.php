<?php

namespace Emdrive\Service;

use Emdrive\Command\ScheduledCommandInterface;
use Emdrive\DependencyInjection\Config;

class PidService
{
    /**
     * @var LockService
     */
    private $lockService;

    /**
     * @var string
     */
    private $dir;

    public function __construct(LockService $lockService, Config $config)
    {
        $this->lockService = $lockService;

        $this->dir = trim($config->pidDir, '/');
    }

    public function getPid(ScheduledCommandInterface $command)
    {
        $filename = $this->getFilename($command);
        if (file_exists($filename)) {
            return file_get_contents($filename);
        }
        return '';
    }

    public function save(ScheduledCommandInterface $command)
    {
        $filename = $this->getFilename($command);
        touch($filename);
        $fp = fopen($filename, 'r+');
        fputs($fp, \getmypid());
    }

    public function remove(ScheduledCommandInterface $command)
    {
        $filename = $this->getFilename($command);
        if (file_exists($filename)) {
            file_put_contents($filename, '');
        }
    }

    private function getFilename(ScheduledCommandInterface $command)
    {
        return sprintf('%s/%s.pid', $this->dir, preg_replace('/[^a-z0-9\._-]+/i', '-', $command->getLockName()));
    }
}
