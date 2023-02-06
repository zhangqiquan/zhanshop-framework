<?php
// +----------------------------------------------------------------------
// | zhanshop-php / HttpReload.php    [ 2023/2/2 11:23 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\console\crontab;

use zhanshop\App;
use Swoole\Timer;

class WatchCronTab
{
    protected $timerId;
    public function execute($serv){
        $this->timerId = Timer::tick(2000, function () use ($serv){
            // 检查代码是否发生变化
            $serv->task('watchTask'); // 给task进程投递http重载任务
        });
    }

    public function close(){
        \Swoole\Timer::clear($this->timerId);
    }
}