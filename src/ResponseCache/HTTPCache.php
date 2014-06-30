<?php

namespace Modules\Cache\ResponseCache;

use Miny\Application\Events\FilterRequestEvent;
use Miny\Application\Events\FilterResponseEvent;
use Miny\Factory\Container;
use Miny\Log\AbstractLog;
use Miny\Log\Log;
use Modules\Cache\AbstractCacheDriver;

class HTTPCache
{
    private $container;
    private $cache;
    private $log;
    private $from_cache;
    private $paths;
    private $pathPatterns;
    private $cacheLifetime;

    public function __construct(
        Container $container,
        AbstractCacheDriver $cache,
        AbstractLog $log,
        $cacheLifetime = 600
    ) {
        $this->container     = $container;
        $this->cache         = $cache;
        $this->log           = $log;
        $this->from_cache    = false;
        $this->cacheLifetime = $cacheLifetime;
        $this->paths         = array();
        $this->pathPatterns  = array();
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
            $path                 = preg_quote($path, '/');
            $this->pathPatterns[] = strtr($path, array('\*' => '(.*?)'));
        } else {
            $this->paths[] = $path;
        }
    }

    /**
     * @param FilterRequestEvent $event
     *
     * @return CachedResponse|null
     */
    public function fetch(FilterRequestEvent $event)
    {
        if ($this->cache === null) {
            return null;
        }
        $request = $event->getRequest();
        if (!$this->cache->has($request->getUrl())) {
            return null;
        }
        /** @var $response CachedResponse */
        $response = $this->cache->get($request->getUrl());
        if (empty($response)) {
            return null;
        }
        $this->log->write(Log::INFO, 'HTTPCache', 'Cache hit for path: %s', $request->getUrl());
        $this->from_cache = true;

        $main = $this->container->get('Miny\\HTTP\\Response');
        foreach ($response->getParts() as $part) {
            if ($part instanceof CachedResponse) {
                $main->addResponse(
                    $this->container->get('Miny\\Application\\Dispatcher')->dispatch(
                        $part->getRequest()
                    )
                );
            } else {
                $main->addContent($part);
            }
        }

        return $response;
    }

    /**
     * @param string $path
     *
     * @return boolean
     */
    private function checkPathPatterns($path)
    {
        foreach ($this->pathPatterns as $pattern) {
            if (preg_match('/^' . $pattern . '$/D', $path)) {
                return true;
            }
        }

        return false;
    }

    public function store(FilterResponseEvent $event)
    {
        if ($this->cache === null) {
            return;
        }
        $request = $event->getRequest();
        if (!in_array($request->getUrl(), $this->paths)) {
            if (!$this->checkPathPatterns($request->getUrl())) {
                return;
            }
        }
        $response = $event->getResponse();
        if (!$this->from_cache && $response->isCode(200) && $request->getMethod() === 'GET') {
            $this->cache->store($request->getUrl(), $response, $this->cacheLifetime);
        }
    }
}
