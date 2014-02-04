<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Cache;

use Exception;
use OutOfBoundsException;

abstract class AbstractCacheDriver
{
    protected $data = array();
    protected $keys = array();
    protected $ttls = array();

    public function __construct()
    {
        $this->gc();
        $this->index();
    }

    abstract protected function gc();

    abstract protected function index();

    public function __destruct()
    {
        $this->close();
    }

    abstract protected function close();

    abstract public function get($key);

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

    protected function checkKey($key)
    {
        if (!$this->has($key)) {
            throw new OutOfBoundsException('Key not found: ' . $key);
        }
    }

    public function has($key)
    {
        return array_key_exists($key, $this->keys) && $this->keys[$key] != 'r';
    }

    protected function keyNotFound($key, Exception $prev = null)
    {
        throw new OutOfBoundsException('Key not found: ' . $key, 0, $prev);
    }

    protected function saveRequired()
    {
        return (bool)array_intersect($this->keys, array('r', 'm', 'a'));
    }

}
