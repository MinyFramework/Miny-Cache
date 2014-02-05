<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Cache;

use Miny\Application\BaseApplication;

class Module extends \Miny\Modules\Module
{

    public function defaultConfiguration()
    {
        return array(
            'cache' => array(
                'http_cache'    => array(
                    'enabled'        => false,
                    'cache_lifetime' => 600,
                    'paths'          => array()
                ),
                'apc'           => array(
                    'storage_key' => 'miny_cache'
                ),
                'session'       => array(
                    'storage_key' => 'miny_cache'
                ),
                'orm'           => array(
                    'table' => 'miny_cache'
                ),
                'sqlite_memory' => array(
                    'table' => 'miny_cache'
                ),
                'sql'           => array(
                    'table' => 'miny_cache'
                ),
                'default_cache' => __NAMESPACE__ . '\\Drivers\\Session',
            )
        );
    }

    public function init(BaseApplication $app)
    {
        $factory    = $app->getContainer();
        $parameters = $app->getParameterContainer();

        $factory->addAlias(__NAMESPACE__ . '\Drivers\SQL', null, array(1 => '@cache:sql:table'));
        $factory->addAlias(__NAMESPACE__ . '\Drivers\Session', null, array('@cache:session:storage_key'));
        $factory->addAlias(__NAMESPACE__ . '\Drivers\APC', null, array('@cache:apc:storage_key'));
        $factory->addAlias(__NAMESPACE__ . '\Drivers\ORM', null, array(1 => '@cache:orm:table'));
        $factory->addAlias(__NAMESPACE__ . '\Drivers\SQLite_Memory', null, array('@cache:sqlite_memory:table'));

        $factory->addAlias(__NAMESPACE__ . '\AbstractCacheDriver', $parameters['cache']['default_cache']);

        if ($parameters['cache']['http_cache']['enabled']) {
            $factory->addAlias('\Miny\HTTP\Response', __NAMESPACE__ . '\ResponseCache\CachedResponse');

            $httpCache = $factory->get(
                __NAMESPACE__ . '\ResponseCache\HTTPCache',
                array(3 => '@cache:http_cache:cache_lifetime')
            );
            $httpCache->addPaths($parameters['cache']['http_cache']['paths']);

            $events = $factory->get('\Miny\Event\EventDispatcher');
            $events->register('filter_request', array($httpCache, 'fetch'));
            $events->register('filter_response', array($httpCache, 'store'));
        }
    }
}
