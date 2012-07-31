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
 * @version   1.0-dev
 */

namespace Modules\Cache;

use Exception;
use OutOfBoundsException;

abstract class AbstractCacheDriver implements iCacheDriver
{
    protected $data = array();
    protected $keys = array();
    protected $ttls = array();

    public function __construct()
    {
        $this->gc();
        $this->index();
    }

    public function __destruct()
    {
        $this->close();
    }

    protected function checkKey($key)
    {
        if (!$this->has($key)) {
            throw new OutOfBoundsException('Key not found: ' . $key);
        }
    }

    protected abstract function gc();
    protected abstract function index();
    protected abstract function close();
    protected function keyNotFound($key, Exception $prev = NULL)
    {
        throw new OutOfBoundsException('Key not found: ' . $key, 0, $prev);
    }

    public function has($key)
    {
        return array_key_exists($key, $this->keys) && $this->keys[$key] != 'r';
    }

    public function remove($key)
    {
        if (!isset($this->keys[$key])) {
            return;
        }
        if ($this->keys[$key] == 'a') {
            unset($this->keys[$key]);
        } else {
            $this->keys[$key] = 'r';
        }
        unset($this->data[$key]);
        unset($this->ttls[$key]);
    }

    public function store($key, $data, $ttl)
    {
        if (isset($this->keys[$key])) {
            if ($this->keys[$key] != 'a') {
                $this->keys[$key] = 'm';
            }
        } else {
            $this->keys[$key] = 'a';
        }
        $this->data[$key] = $data;
        $this->ttls[$key] = $ttl;
    }

    protected function saveRequired()
    {
        return (bool) array_intersect($this->keys, array('r', 'm', 'a'));
    }

}