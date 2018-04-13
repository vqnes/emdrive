<?php

namespace Emdrive\Service;

use Emdrive\DependencyInjection\Config;
use Emdrive\Storage\StorageInterface;
use Symfony\Component\Console\Helper\Table;

class ScheduleService
{
    const SCHEDULE_INTERVAL = 'interval';
    const SCHEDULE_TIME = 'time';

    const STATUS_STOPPED = 'stopped';
    const STATUS_RUNNING = 'running';
    const STATUS_FAILED = 'failed';
    const STATUS_INTERRUPTED = 'interrupted';

    private $storage;
    private $serverName;

    public function __construct(StorageInterface $storage, Config $config)
    {
        $this->storage = $storage;
        $this->serverName = $config->serverName;
    }

    public function findJobsToBeStarted($limit, $excludeNames)
    {
        $jobs = [];
        foreach ($this->getAll() as $item) {
            if ($limit < 1
                || !$item['next_start_at']
                || $item['next_start_at'] > date('Y-m-d H:i:s')
                || in_array($item['name'], $excludeNames)) {
                continue;
            }
            $limit--;
            $jobs[] = $item['name'];
        }
        return $jobs;
    }

    public function getAll()
    {
        return $this->storage->find(StorageInterface::TABLE_SCHEDULE);
    }

    public function setRunning($name)
    {
        return $this->storage->updateRow(
            StorageInterface::TABLE_SCHEDULE,
            [
                'status' => self::STATUS_RUNNING,
                'server_name' => $this->serverName,
                'last_start_at' => date('Y-m-d H:i:s'),
            ],
            ['name' => $name]
        );
    }

    public function setStopped($name, $isError, $isInterrupted)
    {
        $fields = [
            'server_name' => '',
        ];
        if ($isError || $isInterrupted) {
            $fields['status'] = $isError ? self::STATUS_FAILED : self::STATUS_INTERRUPTED;
            $fields['next_start_at'] = date('Y-m-d H:i:s', time() + 30);
        } else {
            $fields['status'] = self::STATUS_STOPPED;

            if ($row = $this->findByName($name)) {
                $fields['next_start_at'] = $this->getNextStartDate(
                    $row['schedule_type'],
                    $row['schedule_value'],
                    $row['last_start_at']
                );
            }
        }

        return $this->updateJob($name, $fields);
    }

    public function updateJob($name, $fields)
    {
        return $this->storage->updateRow(
            StorageInterface::TABLE_SCHEDULE,
            $fields,
            ['name' => $name]
        );
    }

    public function addJob($fields)
    {
        return $this->storage->insertRow(StorageInterface::TABLE_SCHEDULE, $fields);
    }

    public function removeJob($name)
    {
        return $this->storage->removeRow(StorageInterface::TABLE_SCHEDULE, ['name' => $name]);
    }

    public function findByName($name)
    {
        $rows = $this->storage->find(StorageInterface::TABLE_SCHEDULE, ['name' => $name]);

        return current($rows);
    }

    public function createTable()
    {
        return $this->storage->createScheduleTable();
    }


    private function getNextStartDate($scheduleType, $scheduleValue, $lastStartAt)
    {
        if ($scheduleType) {
            $now = date('Y-m-d H:i:s');
            if (!$lastStartAt) {
                $nextTime = $now;
            } else {
                $lastStartTime = strtotime($lastStartAt);

                if (self::SCHEDULE_INTERVAL == $scheduleType) {
                    $nextTime = date('Y-m-d H:i:s', strtotime('+' . $scheduleValue, $lastStartTime));
                } else {
                    list($hour, $minute) = explode(":", $scheduleValue);

                    $hour = str_pad($hour, 2, '0', \STR_PAD_LEFT);
                    $minute = str_pad($minute, 2, '0', \STR_PAD_LEFT);

                    $nextTime = date("Y-m-d $hour:$minute:00", strtotime('+1 day', $lastStartTime));
                }
            }
            return max($now, $nextTime);
        }
        return null;
    }

    public function drawSchedule($output, array $schedule)
    {
        $table = new Table($output);

        if (!$schedule) {
            return;
        }
        $headers = array_map(function ($val) {
            return ucfirst(str_replace('_', ' ', $val));
        }, array_keys($schedule[0]));

        $table->setHeaders($headers);

        $statusFormats = [
            'failed'        => '<error>%s</error>',
            'interrupted'   => '<comment>%s</comment>',
            'running'       => '<info>%s</info>',
            'stopped'       => '%s',
        ];
        foreach ($schedule as $i => $job) {
            $job['status'] = $output->getFormatter()->format(
                sprintf($statusFormats[$job['status']], $job['status'])
            );
            $table->addRow($job);
        }

        $table->render();
    }
}
