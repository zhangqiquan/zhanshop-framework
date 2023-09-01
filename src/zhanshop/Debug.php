<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / Debug.php    [ 2023/9/1 16:11 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop;

class Debug
{
    public function handle(Request &$request, \Closure &$next){
        return $next($next);
    }
}