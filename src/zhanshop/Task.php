<?php
// +----------------------------------------------------------------------
// | zhanshop-php / Task.php    [ 2023/2/2 11:08 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop;

class Task
{
    /**
     * server对象
     * @var Swoole\Server
     */
    protected $server;
    public function __construct(mixed &$server)
    {
        $this->server = $server;
    }

    /**
     * 获取server对象
     * @return Swoole\Server
     */
    public function getServer(){
        return $this->server;
    }

    /**
     * 执行task任务
     * @param array $call
     * @param array $param
     * @return void
     */
    public function callback(array $callback, ...$value){
        $this->server->task([
            'callback' => $callback,
            'value' => $value
        ]);
    }
}