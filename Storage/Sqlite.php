<?php

namespace Emdrive\Storage;

class Sqlite implements StorageInterface
{
    private $db;

    public function __construct($dsn, $username = null, $password = null)
    {
        $this->db = new \PDO($dsn);
    }

    public function find($table, array $where = [])
    {
        $stmt = $this->db->query(sprintf(
            'SELECT * FROM %s %s',
            $table,
            ($where ? ' WHERE ' . $this->arrayToSql($where, 'AND') : '')
        ));

        $rows = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $item) {
            $rows[] = $item;
        }
        return $rows;
    }

    public function updateRow($table, array $fields, array $where)
    {
        $this->db->exec(sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            $this->arrayToSql($fields, ','),
            $this->arrayToSql($where, ' AND ')
        ));
    }

    public function insertRow($table, array $fields)
    {
        $this->db->exec(sprintf(
            'INSERT INTO `%s` ( %s ) VALUES ( %s )',
            $table,
            join(",", array_keys($fields)),
            join(",", array_map(function ($val) {return $this->db->quote($val);}, $fields))
        ));
    }

    public function removeRow($table, array $where)
    {
        $this->db->exec(sprintf(
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
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS `' . self::TABLE_SCHEDULE . '` (
              `name` varchar(50) NOT NULL,
              `server_name` varchar(100) DEFAULT NULL,
              `status` varchar(50) DEFAULT NULL,
              `last_start_at` datetime DEFAULT NULL,
              `next_start_at` datetime DEFAULT NULL,
              `schedule_type` varchar(10) DEFAULT NULL,
              `schedule_value` varchar(10) DEFAULT NULL
            )'
        );
    }
}
