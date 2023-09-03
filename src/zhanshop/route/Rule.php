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
    public $extra = [];
    public $middleware = [];
    public $cache = -1;

    public function __construct(string $method, string $uri, array $handler)
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->handler = $handler;
    }

    public function middleware(array $class){
        $middlewares = [];
        foreach($class as $middleware){
            $middlewares[] = function (Request &$request, \Closure &$next) use (&$middleware){
                App::make($middleware)->handle($request, $next);
            };
        }
        $this->middleware = $middlewares;
        return $this;
    }

    public function extra(array $extra){
        $this->extra = $extra;
        return $this;
    }

    public function cache(int $second){
        $this->cache = $second;
        return $this;
    }
}
