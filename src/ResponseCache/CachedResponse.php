<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Cache\ResponseCache;

use Miny\HTTP\Request;
use Miny\HTTP\Response;

/**
 * CachedResponse extends \Miny\HTTP\Response to be cache-able by HTTPCache.
 *
 * @author Dániel Buga <bugadani@gmail.com>
 */
class CachedResponse extends Response
{
    private $request;
    private $contents;

    public function __construct(Request $request)
    {
        $this->request  = $request;
        $this->contents = array();
        parent::__construct();
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function addResponse(Response $response)
    {
        $this->contents[] = $this->getContent();
        $this->clearContent();
        $this->contents[] = $response;
        foreach ($response->getCookies() as $name => $value) {
            $this->setCookie($name, $value);
        }
    }

    public function getParts()
    {
        return $this->contents;
    }

    public function __toString()
    {
        return implode('', $this->contents) . parent::__toString();
    }

    public function serialize()
    {
        return serialize(array(
            'parent'   => parent::serialize(),
            'request'  => $this->request,
            'contents' => $this->contents
        ));
    }

    public function unserialize($serialized)
    {
        $array          = unserialize($serialized);
        $this->request  = $array['request'];
        $this->contents = $array['contents'];
        parent::unserialize($array['parent']);
    }
}
