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
use zhanshop\console\command\Server;
use zhanshop\console\Output;

class Log
{
    protected $channel;

    protected $dirver;

    protected $type;

    protected static $daemonize = false;

    public function __construct($daemonize = false){
        $type = App::config()->get('log.type');
        $this->type = $type;
        if(strpos($type, '\\') === false) $type = '\\zhanshop\\log\\driver\\'.ucfirst($type);

        $capacity = App::config()->get('log.capacity', 20000);
        $this->channel = new Channel($capacity);
        $this->dirver = new $type;
        self::$daemonize = $daemonize;
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
        Timer::tick(100, function () {
            try {
                $this->dirver->write($this);
            }catch (\Throwable $e){
                Error::exceptionHandler($e);
            }

        });
    }

    /**
     * 打印错误日志
     * @param int $level
     * @param string $msg
     * @return void
     */
    public static function errorLog(int $level, string $msg) :void{
        if(self::$daemonize){
            swoole_error_log($level, $msg);
        }else{
            // 直接输出日志
            $msg = '['.date('Y-m-d H:i:s *v').']	LOG LEVEL '.$level.'	'.$msg;
            $stype = 'success';
            if ($level >= 5){
                $stype = 'error';
            }else if($level >= 4){
                $stype = 'info';
            }else if($level >= 2){
                $stype = 'debug';
            }
            App::make(Output::class)->output($msg, $stype);
        }
    }
}