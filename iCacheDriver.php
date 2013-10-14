<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Cache;

interface iCacheDriver
{
    public function has($key);
    public function get($key);
    public function store($key, $data, $ttl);
    public function remove($key);
}