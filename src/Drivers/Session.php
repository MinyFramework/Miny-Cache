<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Cache\Drivers;

use Modules\Cache\AbstractCacheDriver;

class Session extends AbstractCacheDriver
{
    private $storage_key;

    public function __construct($storageKey)
    {
        $this->storage_key = $storageKey;
        parent::__construct();
    }

    protected function gc()
    {
        if (!isset($_SESSION[$this->storage_key])) {
            $_SESSION['cache'] = array();
        } else {
            foreach ($_SESSION[$this->storage_key] as $key => $value) {
                if (!isset($value['expiration']) || $value['expiration'] < time()) {
                    unset($_SESSION[$this->storage_key][$key]);
                }
            }
        }
    }

    protected function index()
    {
        foreach ($_SESSION[$this->storage_key] as $key => $value) {
            $this->keys[$key] = 1;
            $this->data[$key] = unserialize($value['value']);
        }
    }

    public function get($key)
    {
        $this->checkKey($key);
        if (!array_key_exists($key, $this->data)) {
            if (!isset($_SESSION[$this->storage_key][$key])) {
                $this->keyNotFound($key);
            }
            $this->data[$key] = unserialize($_SESSION[$this->storage_key][$key]['value']);
        }
        return $this->data[$key];
    }

    protected function close()
    {
        if (!$this->saveRequired()) {
            return;
        }
        foreach ($this->keys as $key => $state) {
            switch ($state) {
                case 'm':
                case 'a':
                    $_SESSION[$this->storage_key][$key] = array(
                        'expiration' => time() + $this->ttls[$key],
                        'value'      => serialize($this->data[$key])
                    );
                    break;
                case 'r':
                    unset($_SESSION[$this->storage_key][$key]);
                    break;
            }
        }
    }
}
