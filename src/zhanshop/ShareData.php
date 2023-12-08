<?php
// +----------------------------------------------------------------------
// | zhanshop-docker-server / MemoryTable.php    [ 2023/12/4 22:20 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop;

class ShareData
{
    /**
     * @var \Swoole\Table
     */
    protected static $instance = null;
    public static function make(){
        if(!self::$instance){
            $share = App::config()->get('share');
            $capacity = $share['capacity'] ?? 0;
            $fields = $share['field'] ?? [];
            if($capacity && $fields){
                $table = new \Swoole\Table($capacity);
                $types = [
                    'int' => \Swoole\Table::TYPE_INT,
                    'string' => \Swoole\Table::TYPE_STRING,
                    'float' => \Swoole\Table::TYPE_FLOAT
                ];
                foreach ($fields as $k => $field){
                    $name = $field['name'] ?? "field".$k;
                    $type = $field['type'] ?? 'string';
                    $length = $field['length'] ?? 11;
                    $type = $types[$type] ?? \Swoole\Table::TYPE_STRING;
                    if($type == \Swoole\Table::TYPE_STRING){
                        $table->column($name, $type, $length);
                    }else{
                        $table->column($name, $type, $length);
                    }
                }
                $table->create();
                self::$instance = $table;
            }
        }
    }

    /**
     * 获取一行数据
     * @param string $key
     * @param string|null $field
     * @return mixed
     */
    public static function get(string $key, string $field = null){
        return self::$instance->get($key, $field);
    }

    /**
     * 原子自增操作
     * @param string $key
     * @param string $column
     * @param mixed $incrby
     * @return float|int
     */
    public static function incr(string $key, string $column, mixed $incrby = 1){
        return self::$instance->incr($key, $column, $incrby);
    }
    /**
     * 原子自减操作
     * @param string $key
     * @param string $column
     * @param mixed $decrby
     * @return float|int
     */
    public static function decr(string $key, string $column, mixed $decrby = 1){
        return self::$instance->decr($key, $column, $decrby);
    }

    /**
     * 设置行的数据。Table 使用 key-value 的方式来访问数据
     * @param string $key
     * @param array $value
     * @return bool
     */
    public static function set(mixed $key, array $value){
        return self::$instance->set((string)$key, $value);
    }
    /**
     * 删除key
     * @param string $key
     * @return bool
     */
    public static function del(string $key){
        return self::$instance->del($key);
    }

    /**
     * 状态
     * @return array|false
     */
    public static function stats(){
        return self::$instance->stats();
    }

    /**
     * 统计
     * @return int
     */
    public static function count(){
        return self::$instance->count();
    }

    /**
     * 检查 table 中是否存在某一个 key
     * @param string $key
     * @return bool
     */
    public static function exist(string $key){
        return self::$instance->exist($key);
    }

    /**
     * 获取实例
     * @return \Swoole\Table|null
     */
    public static function getInstance(){
        return self::$instance;
    }
}