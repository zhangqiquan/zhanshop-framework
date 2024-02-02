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
use zhanshop\console\TaskManager;
use zhanshop\Crontab;

class WatchServCronTab extends Crontab
{
    public function configure()
    {
        $this->setTitle("定时检查代码并热更新")->interval(3);
    }

    public function execute()
    {
        App::make(TaskManager::class)->callback(WatchServTask::class);
    }
}