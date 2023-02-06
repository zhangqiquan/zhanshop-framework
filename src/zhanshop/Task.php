<?php
// +----------------------------------------------------------------------
// | zhanshop-php / Task.php    [ 2023/2/2 11:08 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop;

use zhanshop\console\task\WatchCode;

class Task
{
    protected $server;
    protected $tasks = [];
    protected $instances = [];
    public function init(&$server, &$tasks){
        if($this->server == false){
            $this->server = $server;
            $this->tasks = $tasks;
        }
    }

    protected function instance(string $name){
        $val = $this->tasks[$name] ?? App::error()->setError('没有配置/注册名为：'.$name.'的任务');
        if(isset($this->instances[$name])) return $this->instances[$name];
        $obj = new $val();
        $this->instances[$name] = $obj;
        return $obj;
    }

    public function call(string $name){
        $this->instance($name)->execute($this->server);
    }
}