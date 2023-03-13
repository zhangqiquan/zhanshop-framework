<?php
// +----------------------------------------------------------------------
// | zhanshop-php / LogPool.php [ 2023/1/31 下午9:38 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop;

use Swoole\Coroutine\Channel;
use Swoole\Timer;
use Swoole\Coroutine;
use zhanshop\App;

class Log
{
    protected $channel;

    protected $dirver;

    protected $type;

    protected $timerId;

    public function __construct(){
        $type = App::config()->get('log.type');
        $this->type = $type;
        if(strpos($type, '\\') === false) $type = '\\zhanshop\\log\\driver\\'.ucfirst($type);
        $capacity = 20000;
        $this->channel = new Channel($capacity);
        $this->dirver = new $type;
    }

    public function push(string $msg, $level = 'INFO'){
        $msg = '['.date('Y-m-d H:i:s').']###['.$level.']###'.$msg;
        $this->channel->push($msg);
    }

    public function pop(){
        $time = 0.01;
        return $this->channel->pop($time);
    }

    public function execute(){
        // 0.1秒执行一次
        $this->timerId = Timer::tick(100, function () {
            try {
                $this->dirver->write($this);
            }catch (\Throwable $e){
                swoole_error_log(SWOOLE_LOG_ERROR, "日志写入出错：".$e->getMessage());
            }

        });
    }

    public function close(){
        \Swoole\Timer::clear($this->timerId);
    }
}