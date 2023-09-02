<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2021 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace zhanshop\route;

use zhanshop\App;
use zhanshop\Error;
use zhanshop\Request;
use zhanshop\Response;

/**
 * 路由调度基础类
 */
class Dispatch
{
    protected $name;
    protected $version;
    protected $uri;
    protected $method;

    /**
     * 路由检查
     * @return void
     */
    public function check(string &$name, Request &$request){
        // 检查路由是否存在
        $params = explode("/", $request->server('request_uri'));
        $version = $params[1] ? $params[1] : 'v1';
        $uri     = isset($params[2]) ? '/'.$params[2] : '/index.index';
        $this->method = $request->server('request_method', 'GET');
        var_dump($name, $version, $uri, $this->method);
        $route = App::route()->getRule()->getBind($name, $version, $uri, $this->method);
        if(!$route) App::error()->setError('您所访问的API不存在', Error::NOT_FOUND);

        foreach ($route['extra'] as $k => $v){
            $request->setData($v, $params[$k + 3] ?? null);
        }

        $request->setRoure($route); // 设置当前路由

    }

    /**
     * 执行路由调度
     * @param string $name
     * @param Request $request
     * @param Response $servResponse
     * @return mixed
     */
    public function run(string &$name, Request &$request, Response &$servResponse){
        $roure = $request->getRoure();
        $action = $roure['handler'][1];
        $controller = $roure['handler'][0];
        // 在台式机上测试性能strtolower
        return App::make($controller)->$action($request, $servResponse);
    }
}
