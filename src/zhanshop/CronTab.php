<?php
// +----------------------------------------------------------------------
// | zhanshop_admin / CronTab.php [ 2023/4/19 下午8:57 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop;

use Swoole\Timer;

abstract class CronTab
{

    /**
     * 执行间隔
     * @var int
     */
    protected int $interval = 60000; // 运行间隔时间

    protected $isAfter = false; // 是否一次性定时器 一次定时器支持在任务结束后给定一个下次再执行的时间
    /**
     * 所属的server名称
     * @var string
     */
    protected $serverName = 'http'; // server名称

    /**
     * 实例化并执行逻辑
     * @param mixed $server
     */
    public function __construct(mixed $server)
    {
        $this->execute($server);
    }

    /**
     * 执行handle
     * @param mixed $server
     * @param string $serverName
     * @return void
     */
    public function execute(mixed &$server){

        $this->addTimer(function ()use (&$server){
            $this->interval = 0;
            $this->handle($server);
            if($this->isAfter && $this->interval > 0) $this->execute($server); // 继续回调
        });
    }

    /**
     * 添加定时器
     * @param int $interval
     * @param $callBack
     * @return void
     */
    protected function addTimer($callBack){
        if($this->isAfter){
            Timer::after($this->interval, $callBack);
        }else{
            Timer::tick($this->interval, $callBack);
        }
    }

    /**
     * 处理逻辑代码
     * @param mixed $server
     * @return mixed
     */
    abstract public function handle(mixed &$server);

}