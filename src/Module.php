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
            'default_cache' => __NAMESPACE__ . '\\Drivers\\Session'
        );
    }

    public function init(BaseApplication $app)
    {
        $container = $app->getContainer();

        $container->addConstructorArguments(
            __NAMESPACE__ . '\\Drivers\\SQL',
            array(
                1 => array($this->getConfiguration('sql:table'))
            )
        );
        $container->addConstructorArguments(
            __NAMESPACE__ . '\\Drivers\\Session',
            $this->getConfiguration('session:storage_key')
        );
        $container->addConstructorArguments(
            __NAMESPACE__ . '\\Drivers\\APC',
            $this->getConfiguration('apc:storage_key')
        );
        $container->addConstructorArguments(
            __NAMESPACE__ . '\\Drivers\\ORM',
            array(
                1 => array(
                    $this->getConfiguration(
                        'orm:table'
                    )
                )
            )
        );
        $container->addConstructorArguments(
            __NAMESPACE__ . '\\Drivers\\SQLite_Memory',
            $this->getConfiguration('sqlite_memory:table')
        );

        $container->addAlias(
            __NAMESPACE__ . '\\AbstractCacheDriver',
            $this->getConfiguration('default_cache')
        );

        if (!$this->getConfiguration('http_cache:enabled')) {
            return;
        }

        $container->addAlias(
            '\\Miny\\HTTP\\Response',
            __NAMESPACE__ . '\\ResponseCache\\CachedResponse'
        );

        $httpCache = $container->get(
            __NAMESPACE__ . '\\ResponseCache\\HTTPCache',
            array(
                3 => array($this->getConfiguration('http_cache:cache_lifetime'))
            )
        );
        $httpCache->addPaths($this->getConfiguration('http_cache:paths'));

        $events = $container->get('\\Miny\\Event\\EventDispatcher');
        $events->register('filter_request', array($httpCache, 'fetch'));
        $events->register('filter_response', array($httpCache, 'store'));
    }
}
