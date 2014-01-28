<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Cache\Drivers;

use PDO;

class SQLite_Memory extends SQL
{
    protected static $queries = array(
        'gc'           => 'DELETE FROM `%s` WHERE `expiration` < DATETIME(\'now\')',
        'index'        => 'SELECT id FROM %s',
        'select'       => 'SELECT `data` FROM `%s` WHERE `id` = :key',
        'delete'       => 'DELETE FROM `%s` WHERE `id` = :key',
        'modify'       => 'REPLACE INTO `%s` (`id`, `data`, `expiration`)
                VALUES(:id, :value, :expiration)',
        'create_table' => 'CREATE TABLE IF NOT EXISTS %s (
            id char(50) PRIMARY KEY,
            data TEXT,
            expiration INTEGER
        )'
    );

    public function __construct($table_name)
    {
        $driver = new PDO('sqlite::memory:', null, null,
                array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION
        ));
        $driver->query(sprintf(self::$queries['create_table'], $table_name));
        parent::__construct($driver, $table_name);
    }
}
