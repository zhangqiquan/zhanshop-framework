<?php
// +----------------------------------------------------------------------
// | zhanshop-php / PDOConnectionPool.php    [ 2023/1/31 17:19 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\cache;
use Swoole\Database\RedisConfig;
use Swoole\Database\RedisPool;

/**
 * @mixin \Redis
 */

class RedisConnectionPool
{
    protected $pool;

    protected $timeoutPool;

    public function __construct(array $config){
        $maxConnections = $config['pool']['max_connections'] ?? 10;
        $this->timeoutPool = $config['pool']['timeout'] ?? 0.1;
        $this->pool = new RedisPool((new RedisConfig)
            ->withHost($config['host'])
            ->withPort((int)$config['port'])
            ->withAuth($config['password'] ?? '')
            ->withDbIndex((int)$config['select'] ?? 0)
            ->withTimeout((float)$config['timeout'] ?? 1)
            ,$maxConnections
        );
    }

    public function callback($call){
        $redis = $this->getRedis();
        try {
            $call($redis);
            $this->recoveryRedis($redis);
        }catch (\Throwable $e){
            $this->recoveryRedis($redis);
            throw new \Exception($e->getMessage().' '.$e->getFile().':'.$e->getLine(), $e->getCode());
        }
    }

    /**
     * 获取通道上的pdo
     * @return \Redis
     */
    protected function getRedis(){
        $redis = $this->pool->get($this->timeoutPool);
        if($redis == false){
            throw new \Exception('cache连接池耗尽【服务暂时不可用】', 503);
        }
        return $redis;
    }

    /**
     * 回收pdo至通道
     * @param $pdo
     * @return void
     */
    protected function recoveryRedis($redis){
        $this->pool->put($redis);
    }

    public function __call(string $name, array $arguments){
        $redis = $this->getRedis();
        try {
            $data = $redis->$name(...$arguments);
            $this->recoveryRedis($redis);
            return $data;
        }catch (\Throwable $e){
            $this->recoveryRedis($redis);
            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }
}