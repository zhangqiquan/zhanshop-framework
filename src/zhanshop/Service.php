<?php
// +----------------------------------------------------------------------
// | flow-course / Service.php    [ 2021/10/26 5:59 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace zhanshop;


class Service
{
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
    protected static function getInstance(mixed $service){
        if (isset(self::$instances[$service])) {
            return self::$instances[$service];
        }
        $obj = new $service();
        return self::$instances[$service] = $obj;
    }

    /**
     * 获取容器
     * @param mixed $service
     * @return mixed
     */
    public function get(mixed $service){
        return self::getInstance($service);
    }

    /**
     * 销毁容器
     * @param mixed $service
     */
    public function delete(mixed $service){
        if (isset(self::$instances[$service])) {
            unset(self::$instances[$service]);
        }
    }
}