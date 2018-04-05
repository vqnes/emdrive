<?php

namespace Emdrive\Storage;


interface StorageInterface
{
    const TABLE_SCHEDULE = 'emdrive_schedule';

    public function __construct($dsn, $username = null, $password = null);

    public function find($table, array $where = []);

    public function updateRow($table, array $fields, array $where);

    public function insertRow($table, array $fields);

    public function removeRow($table, array $where);

    public function createScheduleTable();
}
