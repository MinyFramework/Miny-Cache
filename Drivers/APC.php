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
 *
 */

namespace Modules\Cache\Drivers;

use \Modules\Cache\AbstractCacheDriver;

class APC extends AbstractCacheDriver
{
    protected function index()
    {
        $keys = apc_fetch('cache.index', $success);
        if ($success) {
            foreach ($keys as $key) {
                $this->keys[$key] = 1;
            }
        }
    }

    protected function gc()
    {

    }

    public function get($key)
    {
        if (!array_key_exists($key, $this->data)) {
            if (!apc_exists('cache.' . $key)) {
                $this->keyNotFound($key);
            }
            $data = apc_fetch('cache.' . $key, $success);
            if (!$success) {
                $this->keyNotFound($key);
            }
            $this->data[$key] = $data;
        }
        return $this->data[$key];
    }

    protected function close()
    {
        if (!$this->saveRequired()) {
            return;
        }
        apc_store('cache.index', array_keys($this->keys));
        foreach ($this->keys as $key => $state) {
            switch ($state) {
                case 'm':
                case 'a':
                    apc_store('cache.' . $key, $this->data[$key], $this->ttls[$key]);
                    break;
                case 'r':
                    apc_remove('cache.' . $key);
                    break;
            }
        }
    }

}