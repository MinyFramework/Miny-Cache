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
                    'orm'   => '&orm',
                    'table' => 'miny_cache'
                ),
                'sqlite_memory' => array(
                    'table' => 'miny_cache'
                ),
                'sql'           => array(
                    'driver' => '&pdo',
                    'table'  => 'miny_cache'
                ),
                'default_cache' => 'session',
            )
        );
    }

    public function init(BaseApplication $app)
    {
        $factory    = $app->getFactory();
        $parameters = $factory->getParameters();

        $factory->add('sql_cache', __NAMESPACE__ . '\Drivers\SQL')
                ->setArguments('@cache:sql:driver', '@cache:sql:table');
        $factory->add('session_cache', __NAMESPACE__ . '\Drivers\Session')
                ->setArguments('@cache:session');
        $factory->add('apc_cache', __NAMESPACE__ . '\Drivers\APC')
                ->setArguments('@cache:apc');
        $factory->add('orm_cache', __NAMESPACE__ . '\Drivers\ORM')
                ->setArguments('@cache:orm:manager', '@cache:orm:table');
        $factory->add('sqlite_memory_cache', __NAMESPACE__ . '\Drivers\SQLite_Memory')
                ->setArguments('@cache:sqlite_memory:table');

        $default_cache = $parameters['cache']['default_cache'];

        if (in_array($default_cache, array('sql', 'session', 'apc', 'orm', 'sqlite_memory'))) {
            $factory->addAlias('cache', $default_cache . '_cache');
        }

        if ($parameters['cache']['http_cache']['enabled']) {
            $factory->add('response', __NAMESPACE__ . '\ResponseCache\CachedResponse')
                    ->setArguments('&request');

            $factory->add('http_cache', __NAMESPACE__ . '\ResponseCache\HTTPCache')
                    ->setArguments('&app', '&cache', '&log', '@cache:http_cache:cache_lifetime')
                    ->addMethodCall('addPaths', '@cache:http_cache:paths');

            $factory->getBlueprint('events')
                    ->addMethodCall('register', 'filter_request', '*http_cache::fetch')
                    ->addMethodCall('register', 'filter_response', '*http_cache::store');
        }
    }
}
