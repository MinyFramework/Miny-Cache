<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Cache\Drivers;

use Modules\Cache\AbstractCacheDriver;
use OutOfBoundsException;
use PDO;

class SQL extends AbstractCacheDriver
{
    protected static $queries = array(
        'gc'     => 'DELETE FROM `%s` WHERE `expiration` < NOW()',
        'index'  => 'SELECT `id` FROM `%s` WHERE `expiration` >= NOW()',
        'select' => 'SELECT `data` FROM `%s` WHERE `id` = :key',
        'delete' => 'DELETE FROM `%s` WHERE `id` = :key',
        'modify' => 'REPLACE INTO `%s` (`id`, `data`, `expiration`)
                VALUES(:id, :value, :expiration)'
    );
    private $table_name;
    protected $driver;

    public function __construct(PDO $driver, $table_name)
    {
        $this->driver = $driver;
        $this->table_name = $table_name;
        parent::__construct();
    }

    protected function getQuery($query)
    {
        if (!isset(static::$queries[$query])) {
            throw new OutOfBoundsException('Query not set: ' . $query);
        }
        return sprintf(static::$queries[$query], $this->table_name);
    }

    protected function getStatement($query)
    {
        return $this->driver->prepare($this->getQuery($query));
    }

    protected function gc()
    {
        $this->driver->exec($this->getQuery('gc'));
    }

    protected function index()
    {
        foreach ($this->driver->query($this->getQuery('index')) as $row) {
            $this->keys[$row['id']] = 1;
        }
    }

    public function get($key)
    {
        $this->checkKey($key);
        if (!array_key_exists($key, $this->data)) {
            $statement = $this->getStatement('select');
            $statement->bindValue(':key', $key);
            $statement->execute();
            $data = $statement->fetchColumn(0);
            if(!$data) {
                //the key was deleted during an other request...
                $this->keyNotFound($key);
            }
            $this->data[$key] = unserialize($data);
        }
        return $this->data[$key];
    }

    protected function close()
    {
        $save = false;
        if (in_array('r', $this->keys)) {
            $save = true;
            $delete_statement = $this->getStatement('delete');
        }
        if (in_array('m', $this->keys) || in_array('a', $this->keys)) {
            $save = true;
            $modify_statement = $this->getStatement('modify');
        }

        if (!$save) {
            return;
        }
        $this->driver->beginTransaction();
        foreach ($this->keys as $key => $state) {
            switch ($state) {
                case 'm':
                case 'a':
                    $ttl = $this->ttls[$key];
                    $array = array(
                        'id'         => $key,
                        'expiration' => date('Y-m-d H:i:s', time() + $ttl),
                        'value'      => serialize($this->data[$key])
                    );
                    $modify_statement->execute($array);
                    break;
                case 'r':
                    $delete_statement->bindValue(':key', $key);
                    $delete_statement->execute();
                    break;
            }
        }
        $this->driver->commit();
    }

}

