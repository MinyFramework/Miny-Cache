<?php

namespace Modules\Cache\ResponseCache;

use Miny\Application\Application;
use Miny\HTTP\Request;
use Miny\Log;
use Modules\Cache\AbstractCacheDriver;

class HTTPCache
{
    private $application;
    private $cache;
    private $log;
    private $from_cache;
    private $paths;
    private $path_patterns;
    private $cache_lifetime;

    public function __construct(Application $app, AbstractCacheDriver $cache, Log $log, $cache_lifetime = 600)
    {
        $this->application    = $app;
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
        if ($this->cache->has($request->url)) {
            $response = $this->cache->get($request->url);
            if (!empty($response)) {
                $this->log->info('Cache hit for path: %s', $request->url);
                $this->from_cache = true;
                $main = $this->application->getFactory()->get('response');
                foreach ($response->getParts() as $part) {
                    if ($part instanceof CachedResponse) {
                        $main->addResponse($this->application->dispatch($part->getRequest()));
                    } else {
                        $main->addContent($part);
                    }
                }
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

    public function store(Request $request, CachedResponse $response)
    {
        if ($this->cache === null) {
            return;
        }
        if (!in_array($request->url, $this->paths)) {
            if (!$this->checkPathPatterns($request->url)) {
                return;
            }
        }
        if (!$this->from_cache && $response->isCode(200) && $request->method === 'GET') {
            $this->cache->store($request->url, $response, $this->cache_lifetime);
        }
    }
}