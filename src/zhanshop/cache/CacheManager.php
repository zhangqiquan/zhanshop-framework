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
    /**
     * @var array
     */
    protected $cacheConnectionPools = [];
    public function __construct(){
        $connections = App::config()->get('cache.connections');
        foreach ($connections as $k => $v){
            $this->cacheConnectionPools[$k] = new RedisConnectionPool($v);
        }
    }

    /**
     * @param string $connection
     * @return RedisConnectionPool
     * @throws \Exception
     */
    public function get(string $connection){
        return $this->cacheConnectionPools[$connection] ?? throw new \Exception('cache.connections.'.$connection.'未定义');
    }

    public function clean(){
        $this->cacheConnectionPools = [];
    }

}