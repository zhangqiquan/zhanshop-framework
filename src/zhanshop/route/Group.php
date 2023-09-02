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
use zhanshop\Request;

class Group
{
    protected string $prefix;
    protected mixed $callback;
    protected float $cache = 0;
    protected array $middleware = [];

    protected array $bind = [];

    public function __construct(string $prefix, callable &$callback)
    {
        echo "Group构造";
        $this->prefix = $prefix;
        $this->callback = $callback;
    }

    public function execute(){
        $callback = $this->callback;
        $callback();
    }

    public function bindRoute(array $rules){
        $this->bind = $rules;
    }


    /**
     * 设置中间件
     * @param array $class
     * @return void
     */
    public function middleware(array $class) :Rule{
        foreach($class as $name){
            $this->middleware[] = function (Request &$request, \Closure &$next) use (&$name){
                App::make($name)->handle($request, $next);
            };
        };
        return $this;
    }

    /**
     * 设置header请求头缓存
     * @param int $time
     * @return void
     */
    public function cache(int $time){
        foreach($this->bind as $v){
            $v->cache = $time;
        }
        return $this;
    }

    public function __destruct()
    {
        // 西沟的时候
        $dispatch = App::make(Dispatch::class);
        foreach($this->bind as $v){
            $v->uri = $this->prefix.'.'.$v->uri;
            $dispatch->regRoute($v);
        }
        echo "Group西沟";
    }

}