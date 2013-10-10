<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Cache\Drivers;

use Modules\Cache\AbstractCacheDriver;

class APC extends AbstractCacheDriver
{
    protected function index()
    {
        $keys = apc_fetch('cache.index', $success);
        if ($success) {
            $time = time();
            foreach ($keys as $key => $expiration) {
                if ($expiration >= $time) {
                    $this->keys[$key] = $expiration;
                }
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
        $keys = array();
        foreach ($this->keys as $key => $state) {
            switch ($state) {
                case 'm':
                case 'a':
                    $keys[$key] = time() + $this->ttls[$key];
                    apc_store('cache.' . $key, $this->data[$key], $this->ttls[$key]);
                    break;
                case 'r':
                    apc_remove('cache.' . $key);
                    break;
                default:
                    $keys[$key] = $state;
                    break;
            }
        }
        apc_store('cache.index', $keys);
    }

}

