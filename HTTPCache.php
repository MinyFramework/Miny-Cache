<?php

namespace Modules\Cache;

use Miny\HTTP\Request;
use Miny\HTTP\Response;
use Miny\Log;

class HTTPCache
{
    private $cache;
    private $log;
    private $from_cache;
    private $paths;
    private $path_patterns;
    private $cache_lifetime;

    public function __construct(iCacheDriver $cache, Log $log, $cache_lifetime = 600)
    {
        $this->cache          = $cache;
        $this->log            = $log;
        $this->from_cache     = false;
        $this->cache_lifetime = $cache_lifetime;
        $this->paths          = array();
        $this->path_patterns  = array();
    }

    public function addPaths($paths)
    {
        if (is_array($paths)) {
            foreach ($paths as $path) {
                $this->addPath($path);
            }
        } else {
            $this->addPath($paths);
        }
    }

    public function addPath($path)
    {
        if (strpos($path, '*') !== false) {
            $path                  = preg_quote($path, '/');
            $this->path_patterns[] = strtr($path, array('\*' => '(.*?)'));
        } else {
            $this->paths[] = $path;
        }
    }

    public function fetch(Request $request)
    {
        if ($this->cache === null) {
            return;
        }
        if ($this->cache->has($request->path)) {
            list($response, $content) = $this->cache->get($request->path);
            if (!empty($content)) {
                $this->log->info('Cache hit for path: %s', $request->path);
                $this->from_cache = true;
                echo $content;
                return $response;
            }
        }
    }

    /**
     * @param string $path
     * @return boolean
     */
    private function checkPathPatterns($path)
    {
        foreach ($this->path_patterns as $pattern) {
            if (preg_match('/^' . $pattern . '$/D', $path)) {
                return true;
            }
        }
        return false;
    }

    public function store(Request $request, Response $response)
    {
        if ($this->cache === null) {
            return;
        }
        if (!in_array($request->path, $this->paths)) {
            if (!$this->checkPathPatterns($request->path)) {
                return;
            }
        }
        if (!$this->from_cache && $response->isCode(200) && $request->method === 'GET') {
            $this->cache->store($request->path, array($response, ob_get_contents()), $this->cache_lifetime);
        }
    }
}
