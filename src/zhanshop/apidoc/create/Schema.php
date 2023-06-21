<?php

namespace zhanshop\apidoc\create;

use zhanshop\App;
use zhanshop\Error;
use zhanshop\Helper;

class Schema
{
    /**
     * 创建初始化模型
     * @param string $name
     * @param $connection
     * @return void
     */
    public static function create(string $name){
        //$modelCreateCode = Model::create($name);// 先检查model
        $data = self::getCreateSchema($name);
        $schemaPath = App::appPath().DIRECTORY_SEPARATOR.'schema'.DIRECTORY_SEPARATOR.str_replace('.', DIRECTORY_SEPARATOR, $name).'.php';
        Helper::mkdirs(dirname($schemaPath)); // 创建目录 如果这个文件不存在的话
        file_put_contents($schemaPath, '<?php'.PHP_EOL.'return '.var_export($data, true).';');
        return $data;
    }

    /**
     * 获取将要创建的schema
     * @param $table
     * @return array
     */
    public static function getCreateSchema($table){
        $oldSchema = self::getOldSchema($table);
        $newSchema = self::getNewSchema($table);
        foreach($newSchema as $kk => $vv){
            if(isset($oldSchema[$kk])){
                $newSchema[$kk]['search'] = $oldSchema[$kk]['search'] ?? $vv['search'];
                $newSchema[$kk]['value'] = $oldSchema[$kk]['value'] ?? $vv['value'];
                $newSchema[$kk]['in_list'] = $oldSchema[$kk]['in_list'] ?? $vv['in_list'];
                $newSchema[$kk]['in_field'] = $oldSchema[$kk]['in_field'] ?? $vv['in_field'];
                $newSchema[$kk]['width'] = $oldSchema[$kk]['width'] ?? $vv['width'];
                $newSchema[$kk]['value_menu'] = $oldSchema[$kk]['value_menu'] ?? $vv['value_menu'];
                $newSchema[$kk]['input_type'] = $oldSchema[$kk]['input_type'] ?? $vv['input_type'];
            }
        }
        return $newSchema;
    }

    /**
     * 获取老的schema数据
     * @param $table
     * @return void
     */
    public static function getOldSchema($table){
        $schemaFile = App::appPath().DIRECTORY_SEPARATOR.'schema'.DIRECTORY_SEPARATOR.str_replace('.', DIRECTORY_SEPARATOR, $table).'.php';
        $data = [];
        if(file_exists($schemaFile)){
            $data = (array) include $schemaFile;
        }
        return $data;
    }

    public static function getNewSchema($table){
        //$tables = explode('.', $table);
//        $modelName = $table;
//        $table = $tables[0];
//        if(isset($tables[1])) $table = $tables[1]; // 最终的表名
        $data = App::database()->model($table)->query('SHOW FULL COLUMNS FROM '.$table);
        $schemaData = [];
        foreach($data as $v){
            $maxlength = 0;
            $types = explode('(', $v['Type']);
            if(isset($types[1])){
                $maxlength = intval(explode(')', $types[1])[0]);
            }
            $schemaData[$v['Field']] = [
                'field' => $v['Field'],
                'type' => explode(' ', $v['Type'])[0],
                'key' => strtolower($v['Key']),
                'null' => strtolower($v['Null']),
                'default' => $v['Default'],
                'title' => $v['Comment'] ? $v['Comment'] : $v['Field'],
                'search' => false,
                'value' => [],
                'in_list' => true,
                'in_field' => true,
                'width' => 120,
                'value_menu' => '',
                'input_type' => 'text',
                'input_maxlength' => $maxlength,
            ];
        }
        return $schemaData;
    }
}