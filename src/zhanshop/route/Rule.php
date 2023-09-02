<?php
declare (strict_types = 1);

namespace zhanshop\route;

use zhanshop\App;
use zhanshop\Request;
use zhanshop\Route;

/**
 * 路由规则
 */
class Rule
{
    public $app;
    public $version;
    public $uri;
    public $method;
    public $handler;
    public $extra;
    public $middleware;
    public $cache;

    public function __construct(string $method, string $uri, array $handler)
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->handler = $handler;
    }
}
