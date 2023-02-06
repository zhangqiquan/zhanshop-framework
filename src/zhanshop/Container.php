<?php
// +----------------------------------------------------------------------
// | flow-course / Container    [ 2021/10/22 4:45 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace zhanshop;


use zhanshop\cache\CacheManager;
use zhanshop\database\DbManager;

/**
 * 容器管理类 支持PSR-11
 */
class Container
{
    /**
     * 容器对象实例
     * @var
     */
    public static $instance;

    /**
     * 容器对象实例列表
     * @var Container|Closure
     */
    protected static $instances;

    /**
     * 容器获取注册app类
     * @param string $name
     * @param mixed $value
     */
    public static function get(mixed $name, array $args = []){
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }
        $app = static::$instance->registers[$name] ?? throw new \Exception('APP '.$name.'类不存在或者未注册', 500);
        $obj = new $app(...$args);
        return self::$instances[$name] = $obj;
    }
    /**
     * 容器获取app类
     * @param string $name
     */
    public function __get(string $name){
        return self::$instances[$name] ?? throw new \Exception('APP '.$name.'类不存在或者未注册', 500);
    }

    public function __unset($name)
    {
        unset(self::$instances[$name]);
    }

    /**
     * 容器获取app类
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws \Exception
     */
    public function __call(string $name, array $arguments){
        return self::get($name, $arguments);
    }

    /**
     * 容器获取app类
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic($name, array $arguments)
    {
        return self::get($name, $arguments);
    }

    public static function clean(){
        self::$instances = [];
        CacheManager::init();
        DbManager::init();
    }
}
