<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Cache;

use Miny\Application\BaseApplication;

class Module extends \Miny\Application\Module
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
                'default_cache' => 'session'
            )
        );
    }

    public function init(BaseApplication $app)
    {
        $app->add('sql_cache', __NAMESPACE__ . '\Drivers\SQL');
        $app->add('session_cache', __NAMESPACE__ . '\Drivers\Session');
        $app->add('apc_cache', __NAMESPACE__ . '\Drivers\APC');
        $app->add('orm_cache', __NAMESPACE__ . '\Drivers\ORM');

        $default_cache = $app['cache']['default_cache'];

        if (in_array($default_cache, array('sql', 'session', 'apc', 'orm'))) {
            $app->addAlias('cache', $default_cache . '_cache');
        }

        if ($app['cache']['http_cache']['enabled']) {
            $app->add('http_cache', __NAMESPACE__ . '\HTTPCache')
                    ->setArguments('&cache', '&log', '@cache:http_cache:cache_lifetime')
                    ->addMethodCall('addPaths', '@cache:http_cache:paths');

            $app->getBlueprint('events')
                    ->addMethodCall('register', 'filter_request', '*http_cache::fetch')
                    ->addMethodCall('register', 'filter_response', '*http_cache::store');
        }
    }
}
