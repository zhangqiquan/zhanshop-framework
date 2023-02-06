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
    protected static $PDOConnectionPools = [];
    public static function init(){
        $connections = App::config()->get('database.connections');
        foreach ($connections as $k => $v){
            self::$PDOConnectionPools[$k] = new PDOConnectionPool($v);
        }
    }

    /**
     * @param string $connection
     * @return PDOConnectionPool
     * @throws \Exception
     */
    public static function get(string $connection){
        return self::$PDOConnectionPools[$connection] ?? throw new \Exception('database.'.$connection.'未定义');
    }

    public static function clean(){
        self::$PDOConnectionPools = [];
    }
}