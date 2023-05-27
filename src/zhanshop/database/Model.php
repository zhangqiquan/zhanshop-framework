<?php
// +----------------------------------------------------------------------
// | zhanshop-php / Model.php    [ 2023/1/31 18:32 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\database;

use zhanshop\App;

/**
 * @mixin Query
 */
class Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table;

    // 设置当前模型的数据库连接
    protected $connection;

    protected static $instances;

    private Query $query;

    // 软删除字段
    protected $deleteTime = '';

    public function __construct(){
        $type = App::config()->get('database.connections')[$this->connection]['type'] ?? 'mysql';
        $this->query = new Query($this->connection, $type, $this->deleteTime);
        $this->query->table($this->table);
    }

    /**
     * 调用数据库操作方法
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments){
        return $this->query->$name(...$arguments);
    }

    /**
     * 静态调用
     * @param $name
     * @param $arguments
     * @return Query
     */
    public static function __callStatic($name, $arguments){
        $class = get_called_class();
        if(isset(self::$instances[$class]) == false){
            self::$instances[$class] = new static();
        }
        return self::$instances[$class]->query->$name(...$arguments);
    }

}