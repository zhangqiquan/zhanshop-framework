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
    protected $server;

    protected $configure = [];

    public function __construct(mixed $server)
    {
        $this->server = $server;
    }



    /**
     * 添加每分钟下次执行时间
     * @return void
     */
    protected function perMinute(){
        $second = $this->configure['second'];
        $execTime = strtotime('+1 minute', strtotime(date('Y-m-d H:i:00'))) + $second; // 执行时间
        $msec = intval(($execTime - microtime(true)) * 1000);
        Timer::after($msec, [$this, 'executeAfter']);
    }

    /**
     * 添加每小时下次执行时间
     * @return void
     */
    protected function perHour(){
        $minute = $this->configure['minute'];
        $second = $this->configure['second'];
        $execTime = strtotime('+1 hour +'.$minute.' minute', strtotime(date('Y-m-d H:00:00'))) + $second; // 执行时间
        $msec = intval(($execTime - microtime(true)) * 1000);
        Timer::after($msec, [$this, 'executeAfter']);
    }

    /**
     * 添加每天下次执行时间
     * @return void
     */
    protected function perDay(){
        $hour = $this->configure['hour'];
        $minute = $this->configure['minute'];
        $second = $this->configure['second'];
        // 设置下一分钟
        $execTime = strtotime('+1 day +'.$hour.' hour +'.$minute.' minute', strtotime(date('Y-m-d 00:00:00'))) + $second; // 执行时间
        $msec = intval(($execTime - microtime(true)) * 1000);
        Timer::after($msec, [$this, 'executeAfter']);
    }

    /**
     * 设置每月下次执行时间
     * @return void
     */
    protected function perMonth(){
        $day = $this->configure['day'];
        $hour = $this->configure['hour'];
        $minute = $this->configure['minute'];
        $second = $this->configure['second'];
        $execTime = strtotime('+1 month '.'+'.$day.' day +'.$hour.' hour +'.$minute.'minute', strtotime(date('Y-m-01 00:00:00'))) + $second; // 执行时间
        $msec = intval(($execTime - microtime(true)) * 1000);
        Timer::after($msec, [$this, 'executeAfter']);
    }

    /**
     * 设置每年下次执行时间
     * @return void
     */
    protected function perYear(){
        $month = $this->configure['month'];
        $day = $this->configure['day'];
        $hour = $this->configure['hour'];
        $minute = $this->configure['minute'];
        $second = $this->configure['second'];
        // 设置下一分钟
        $execTime = strtotime('+1 year +'.$month.' month +'.$day.' day + '+$hour+' hour +'.$minute.' minute', strtotime(date('Y-01-01 00:00:00')))  + $second; // 执行时间
        $msec = intval(($execTime - microtime(true)) * 1000);
        Timer::after($msec, [$this, 'executeAfter']);
    }


    /**
     * 执行并设定下次执行时间
     * @return void
     */
    public function executeAfter(){
        // 添加下次执行时间
        $afterMethod = $this->configure['type'];
        $this->$afterMethod(); // 恢复下次执行
        $this->execute();
    }

    /**
     * 配置定时任务
     * @return mixed
     */
    abstract public function configure();

    /**
     * 设置每年执行一次 指定每年的x月x号x时x分x秒执行
     * @param int $month
     * @param int $day
     * @param int $hour
     * @param int $minute
     * @param int $second
     * @return void
     */
    public function setPerYearRule(int $month = 0, int $day = 0, int $hour = 0, int $minute = 0, int $second = 0){
        $this->configure = [
            'type' => 'perYear', // 每年
            'month' => $month,
            'day' => $day,
            'hour' => $hour,
            'minute' => $minute,
            'second' => $second
        ];
        // 设置下一分钟
        $execTime = strtotime('+1 year +'.$month.' month +'.$day.' day + '+$hour+' hour +'.$minute.' minute', strtotime(date('Y-01-01 00:00:00')))  + $second; // 执行时间
        $msec = intval(($execTime - microtime(true)) * 1000);
        Timer::after($msec, [$this, 'executeAfter']);
    }

    /**
     * 设置每月执行一次 指定每月的x号x时x分x秒执行
     * @param int $day
     * @param int $hour
     * @param int $minute
     * @param int $second
     * @return void
     */
    public function setPerMonthRule(int $day = 1, int $hour = 0, int $minute = 0, int $second = 0){
        $this->configure = [
            'type' => 'perMonth', // 每月
            'day' => $day,
            'hour' => $hour,
            'minute' => $minute,
            'second' => $second
        ];
        // 设置下一分钟
        $execTime = strtotime('+1 month '.'+'.$day.' day +'.$hour.' hour +'.$minute.'minute', strtotime(date('Y-m-01 00:00:00'))) + $second; // 执行时间
        $msec = intval(($execTime - microtime(true)) * 1000);
        Timer::after($msec, [$this, 'executeAfter']);
    }

    /**
     * 设置每日执行一次 指定每天的x时x分x秒执行
     * @param int $hour
     * @param int $minute
     * @param int $second
     * @return void
     */
    public function setPerDayRule(int $hour = 0, int $minute = 0, int $second = 0){
        $this->configure = [
            'type' => 'perDay', // 每天
            'hour' => $hour,
            'minute' => $minute,
            'second' => $second
        ];
        // 设置下一分钟
        $execTime = strtotime('+1 day +'.$hour.' hour +'.$minute.' minute', strtotime(date('Y-m-d 00:00:00'))) + $second; // 执行时间
        $msec = intval(($execTime - microtime(true)) * 1000);
        Timer::after($msec, [$this, 'executeAfter']);
    }

    /**
     * 设置每小时执行一次 指定每小时的 x分x秒执行
     * @param int $minute
     * @param int $second
     * @return void
     */
    public function setPerHourRule(int $minute = 0, int $second = 0){
        $this->configure = [
            'type' => 'perHour', // 每小时
            'minute' => $minute,
            'second' => $second
        ];
        // 设置下一分钟
        $execTime = strtotime('+1 hour +'.$minute.' minute', strtotime(date('Y-m-d H:00:00'))) + $second; // 执行时间
        $msec = intval(($execTime - microtime(true)) * 1000);
        Timer::after($msec, [$this, 'executeAfter']);
    }
    /**
     * 设置每分钟执行一次 指定每分钟的X秒执行
     * @param int $minute
     * @param int $second
     * @return void
     */
    public function setPerMinuteRule(int $second = 0){
        $this->configure = [
            'type' => 'perMinute', // 每分钟
            'second' => $second
        ];
        // 设置下一分钟
        $execTime = strtotime('+1 minute', strtotime(date('Y-m-d H:i:00'))) + $second; // 执行时间
        $msec = intval(($execTime - microtime(true)) * 1000);
        Timer::after($msec, [$this, 'executeAfter']);
    }

    /**
     * 设置每隔x毫秒执行一次
     * @param int $msec
     * @return void
     */
    public function setPerSecondRule(int $msec){
        Timer::tick($msec, [$this, 'execute']);
    }

    /**
     * 执行handle
     * @param mixed $server
     * @return void
     */
    abstract public function execute();

}