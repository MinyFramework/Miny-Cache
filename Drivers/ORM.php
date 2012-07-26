<?php

/**
 * This file is part of the Miny framework.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version accepted by the author in accordance with section
 * 14 of the GNU General Public License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   Miny/Modules/Cache
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Modules\Cache\Drivers;

use \Modules\ORM\Manager;
use \Modules\Cache\AbstractCacheDriver;

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
            } catch (\OutOfBoundsException $e) {
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