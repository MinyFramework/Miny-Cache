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

class Session extends AbstractCacheDriver
{
    public function gc()
    {
        if (!isset($_SESSION['cache'])) {
            $_SESSION['cache'] = array();
        } else {
            foreach ($_SESSION['cache'] as $key => $value) {
                if (!isset($value['expiration']) || $value['expiration'] < time()) {
                    unset($_SESSION['cache'][$key]);
                }
            }
        }
    }

    public function index()
    {
        foreach ($_SESSION['cache'] as $key => $value) {
            $this->keys[$key] = 1;
            $this->data[$key] = unserialize($value['value']);
        }
    }

    public function get($key)
    {
        $this->checkKey($key);
        if (!array_key_exists($key, $this->data)) {
            if (!isset($_SESSION['cache'][$key])) {
                $this->keyNotFound($key);
            }
            $this->data[$key] = unserialize($_SESSION['cache'][$key]['value']);
        }
        return $this->data[$key];
    }

    public function close()
    {
        $save = (bool) array_intersect($this->keys, array('r', 'm', 'a'));

        if ($save) {
            foreach ($this->keys as $key => $state) {
                switch ($state) {
                    case 'm':
                    case 'a':
                        $_SESSION['cache'][$key] = array(
                            'expiration' => time() + $this->ttls[$key],
                            'value'      => serialize($this->data[$key])
                        );
                        break;
                    case 'r':
                        unset($_SESSION['cache'][$key]);
                        break;
                }
            }
        }
    }

}