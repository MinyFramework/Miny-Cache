<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Cache;

use Miny\Application\Application;

class Module extends \Miny\Application\Module
{
    public function init(Application $app)
    {
        $app->add('sql_cache', __NAMESPACE__ . '\Drivers\SQL');
        $app->add('session_cache', __NAMESPACE__ . '\Drivers\Session');
        $app->add('apc_cache', __NAMESPACE__ . '\Drivers\APC');
        $app->add('orm_cache', __NAMESPACE__ . '\Drivers\ORM');

        $default_cache = $app['cache']['default_cache'];

        if (in_array($default_cache, array('sql', 'session', 'apc', 'orm'))) {
            $app->addAlias('cache', $default_cache . '_cache');
        }
    }

}
