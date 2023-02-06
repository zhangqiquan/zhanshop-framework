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

    protected $timerId;

    public function __construct(){
        $type = App::config()->get('log.type');
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

    protected function write(){
        $logs = "";
        while ($msg = $this->pop()){
            $logs .= $msg.PHP_EOL;
        }
        if($logs) $this->dirver->write($logs);
    }

    public function execute(){
        // 0.1秒执行一次
        $this->timerId = Timer::tick(100, function () {
            $this->write();
        });
    }

    public function close(){
        \Swoole\Timer::clear($this->timerId);
    }
}