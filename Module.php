<?php

/**
 * This file is part of the Miny framework.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version accepted by the author in accordance with section
 * 14 of the GNU General Public License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   Miny/Modules/Cache
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0-dev
 */

namespace Modules\Cache;

use Miny\Application\Application;

class Module extends \Miny\Application\Module
{
    public function init(Application $app, $default_cache = NULL)
    {
        $app->add('sql_cache', __NAMESPACE__ . '\Drivers\SQL');
        $app->add('session_cache', __NAMESPACE__ . '\Drivers\Session');
        $app->add('apc_cache', __NAMESPACE__ . '\Drivers\APC');
        $app->add('orm_cache', __NAMESPACE__ . '\Drivers\ORM');
        if (in_array($default_cache, array('sql', 'session', 'apc', 'orm'))) {
            $app->addAlias('cache', $default_cache . '_cache');
        }
    }

}