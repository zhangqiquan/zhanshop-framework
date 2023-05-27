<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / Group.php    [ 2023/4/29 15:04 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\route;

use zhanshop\App;

class Group extends Rule
{
    protected string $prefix = '';

    protected array $middleware = [];

    protected int $cache = 0;

    protected mixed $route = null;

    protected mixed $crossDomain = [];

    /**
     * 获取分组路由前缀
     * @return string
     */
    public function getPrefix(){
        return $this->prefix;
    }
    /**
     * 设置路由
     * @param array $methods
     * @param string $uri
     * @param array $handler
     * @return void
     */
    public function addGroup(string $name, callable &$route) :Group{
        $this->prefix = $name;
        $this->route = &$route; // 路由执行回调
        return $this; // 测试分组路由
    }


    /**
     * 设置中间件
     * @param array $class
     * @return void
     */
    public function middleware(array $class) :Rule{
        $this->middleware = array_merge($this->middleware, $class);
        return $this;
    }

    /**
     * 获取中间件信息
     * @return array
     */
    public function getMiddleware(){
        return $this->middleware;
    }

    /**
     * 设置header请求头缓存
     * @param int $time
     * @return void
     */
    public function cache(int $time) :Rule{
        $this->cache = $time;
        return $this;
    }

    /**
     * 设置跨域选项
     * @param array $option
     * @return void
     */
    public function crossDomain(array $option){
        $this->crossDomain = array_merge($this->crossDomain, $option);
    }

    /**
     * 获取跨域参数
     * @return array|mixed
     */
    public function getCrossDomain(){
        return $this->crossDomain;
    }

    /**
     * 获取缓存参数
     * @return int
     */
    public function getCache(){
        return $this->cache;
    }

    public function __destruct()
    {
        App::route()->getRule()->setGroup($this);
        if($this->route){
            $route = $this->route;
            $route(); // 调用
        }
        // 置空
        $this->cache = 0;
        $this->middleware = [];
        $this->prefix = '';
    }

}