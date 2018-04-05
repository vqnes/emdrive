<?php

namespace Emdrive\Storage;

class Mysql implements StorageInterface
{
    private $db;

    private $dsn;
    private $username;
    private $password;

    private function getDb()
    {
        if (null === $this->db) {
            $this->db = new \PDO($this->dsn, $this->username, $this->password);
        }
        return $this->db;
    }

    public function __construct($dsn, $username = null, $password = null)
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
    }

    public function find($table, array $where = [])
    {
        $stmt = $this->getDb()->query(sprintf(
            'SELECT * FROM %s %s',
            $table,
            ($where ? ' WHERE ' . $this->arrayToSql($where, 'AND') : '')
        ));


        $rows = [];

        if ($stmt) {
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $item) {
                $rows[] = $item;
            }
        }
        return $rows;
    }

    public function updateRow($table, array $fields, array $where)
    {
        $this->getDb()->exec(sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            $this->arrayToSql($fields, ','),
            $this->arrayToSql($where, ' AND ')
        ));
    }

    public function insertRow($table, array $fields)
    {
        $this->getDb()->exec(sprintf(
            'INSERT INTO `%s` ( %s ) VALUES ( %s )',
            $table,
            join(",", array_keys($fields)),
            join(",", array_map(function ($val) {return $this->getDb()->quote($val);}, $fields))
        ));
    }

    public function removeRow($table, array $where)
    {
        $this->getDb()->exec(sprintf(
            'DELETE FROM `%s` WHERE %s',
            $table,
            $this->arrayToSql($where, 'AND')
        ));
    }

    private function arrayToSql($fields, $glue)
    {
        $parts = [];
        foreach ($fields as $key => $value) {
            $parts[] = "`$key`='$value'";
        }
        return join(' ' . $glue . ' ', $parts);
    }

    public function createScheduleTable()
    {
        $this->getDb()->exec(
            'CREATE TABLE IF NOT EXISTS `' . self::TABLE_SCHEDULE . '` (
              `id` int(20) NOT NULL AUTO_INCREMENT,
              `name` varchar(50) NOT NULL,
              `server_name` varchar(100) DEFAULT NULL,
              `status` varchar(50) DEFAULT NULL,
              `last_start_at` datetime DEFAULT NULL,
              `next_start_at` datetime DEFAULT NULL,
              `schedule_type` varchar(10) DEFAULT NULL,
              `schedule_value` varchar(10) DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB'
        );
    }
}
