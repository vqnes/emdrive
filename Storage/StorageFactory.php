<?php

namespace Emdrive\Storage;

class StorageFactory
{
    public static function createStorage($dsn)
    {
        preg_match('%([^/@]+)@%', $dsn, $matches);

        $username = $password = null;

        if (isset($matches[1])) {
            $parts = explode(':', $matches[1]);
            $username = $parts[0];
            $password = $parts[1] ?? null;
            $dsn = str_replace($matches[0], '', $dsn);
        }

        switch (true) {
            case preg_match('/^mysql/', $dsn):
                return new Mysql($dsn, $username, $password);
                break;
            case preg_match('/^sqlite/', $dsn):
                return new Sqlite($dsn, getenv('EMDRIVE_USERNAME'), getenv('EMDRIVE_PASSWORD'));
                break;
            default:
                throw new \InvalidArgumentException('Unknown storage type');
        }
    }
}
