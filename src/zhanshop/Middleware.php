<?php
// +----------------------------------------------------------------------
// | flow-course / Middleware.php    [ 2021/10/27 6:22 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace zhanshop;

/**
 * 中间件仅提供一次执行
 * Class Middleware
 * @package kernel
 */
class Middleware
{
    /**
     * 执行前置中间件
     * @param Request $request
     * @param array $middlewares
     */
    public function runBefore(mixed &$request, array $middlewares){
        $rep = null;
        foreach($middlewares as $v){
            App::service()->get($v)->handle($request, $rep); // 执行前置中间件
        }
    }

    /**
     * 执行异步中间件
     * @param Request $request
     * @param array $middlewares
     */
    public function runAfter(mixed &$request, mixed &$data, ?array $middlewares = []){
        foreach($middlewares as $v){
            App::service()->get($v)->handle($request, $data); // 执行后置中间件
        }
    }
}