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
    public function configure()
    {
        $this->setPerSecondRule(2000);
    }

    public function execute()
    {
        App::task()->callback([WatchServTask::class, 'check']);
    }
}