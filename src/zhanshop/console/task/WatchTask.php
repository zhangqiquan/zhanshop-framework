<?php
// +----------------------------------------------------------------------
// | zhanshop-php / HttpReload.php    [ 2023/2/2 11:23 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\console\task;
use zhanshop\App;
use zhanshop\console\WatchCode;

class WatchTask
{
    protected $timerId;
    protected $init = false;
    public function init(){
        if($this->init == false){
            App::make(WatchCode::class)->init(App::rootPath());
            $this->init = true;
        }
    }
    public function execute($serv){
        $this->init();
        if(App::make(WatchCode::class)->isChange()){
            $this->init = false;
            $this->init();
            // 加载完新的文件变化记录
            echo date('Y-m-d H:i:s').'###[info]### 准备重载'.PHP_EOL;
            $serv->reload();
        }
    }

    public function close(){
        \Swoole\Timer::clear($this->timerId);
    }
}