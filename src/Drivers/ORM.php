<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Cache\Drivers;

use Modules\Cache\AbstractCacheDriver;
use Modules\ORM\Manager;
use OutOfBoundsException;

class ORM extends AbstractCacheDriver
{
    private $table;

    public function __construct(Manager $manager, $table_name)
    {
        $this->table = $manager->$table_name;
        parent::__construct();
    }

    protected function gc()
    {
        $this->table->deleteRows('expiration < NOW()');
    }

    protected function index()
    {
        foreach ($this->table as $key) {
            $this->keys[$key['id']] = 1;
        }
    }

    public function get($key)
    {
        $this->checkKey($key);
        if (!array_key_exists($key, $this->data)) {
            try {
                $this->data[$key] = unserialize($this->table[$key]['data']);
            } catch (OutOfBoundsException $e) {
                $this->keyNotFound($key, $e);
            }
        }
        return $this->data[$key];
    }

    protected function close()
    {
        if (!$this->saveRequired()) {
            return;
        }
        $db = $this->table->manager->connection;
        $db->beginTransaction();
        foreach ($this->keys as $key => $state) {
            switch ($state) {
                case 'a':
                    $this->table->insert(array(
                        'id'         => $key,
                        'expiration' => date('Y-m-d H:i:s', time() + $this->ttls[$key]),
                        'data'       => serialize($this->data[$key])
                    ));
                    break;
                case 'm':
                    $data = array(
                        'expiration' => date('Y-m-d H:i:s', time() + $this->ttls[$key]),
                        'data'       => serialize($this->data[$key])
                    );
                    $this->table->update($key, $data);
                    break;
                case 'r':
                    $this->table->delete($key);
                    break;
            }
        }
        $db->commit();
    }

}

