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
     * 是否后置中间件
     * @var int
     */
    protected bool $isAfter = false; // 中间件类型 1 前置 2 后置

    /**
     * 获取中间件类型
     * @return int
     */
    public function isAfter(){
        return $this->type;
    }
}