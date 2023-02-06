<?php
// +----------------------------------------------------------------------
// | zhanshop-php / DbPool.php    [ 2023/1/31 18:09 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\cache;

use zhanshop\App;

class CacheManager
{
    protected static $cacheConnectionPools = [];
    public static function init(){
        $connections = App::config()->get('cache.connections');
        foreach ($connections as $k => $v){
            self::$cacheConnectionPools[$k] = new RedisConnectionPool($v);
        }
    }

    /**
     * @param string $connection
     * @return RedisConnectionPool
     * @throws \Exception
     */
    public static function get(string $connection){
        return self::$cacheConnectionPools[$connection] ?? throw new \Exception('cache.connections.'.$connection.'未定义');
    }

    public static function clean(){
        self::$cacheConnectionPools = [];
    }

}