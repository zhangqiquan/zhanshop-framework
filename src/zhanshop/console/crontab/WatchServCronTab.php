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
use zhanshop\console\task\WatchServTask;
use zhanshop\CronTab;

class WatchServCronTab extends CronTab
{
    protected int $interval = 2000; // 运行间隔时间

    public function handle(mixed &$server){
        App::task()->callback([WatchServTask::class, 'check']); // 调用
        //$server->task([WatchServTask::class, 'check']); // 检查是否需要重启
    }
}