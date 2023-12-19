<?php
// +----------------------------------------------------------------------
// | zhanshop-php / DbPool.php    [ 2023/1/31 18:09 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\database;

use zhanshop\App;

class DbManager
{
    protected $PDOConnectionPools = [];
    public function __construct(){
        $connections = App::config()->get('database.connections');
        foreach ($connections as $k => $v){
            $this->PDOConnectionPools[$k] = new PDOConnectionPool($v);
        }
    }

    /**
     * @param string $connection
     * @return PDOConnectionPool
     * @throws \Exception
     */
    public function get(string $connection){
        return $this->PDOConnectionPools[$connection] ?? throw new \Exception('database.connections.'.$connection.'未定义');
    }

    public function clean(){
        $this->PDOConnectionPools = [];
    }
}