<?php

namespace zhanshop\apidoc\create;

use zhanshop\App;
use zhanshop\Error;
use zhanshop\Helper;

class Model
{
    /**
     * 检查模型对应的表是否存在
     * @param $connection
     * @param $name
     * @return void
     */
    protected static function check($connection, $name){
        try {
            $data = App::database()->table($name, $connection)->query('SHOW FULL COLUMNS FROM '.$name);
        }catch (\Throwable $e){
            if($e->getCode() == 1146){
                App::error()->setError('database.connections.'.$connection.'数据库连接'.$name.'表不存在', Error::NOT_FOUND);
            }
            App::error()->setError($e->getMessage());
        }
    }
    /**
     * 创建初始化模型
     * @param string $name
     * @param $connection
     * @return void
     */
    public static function create(string $name){
        $connection = App::config()->get('database.default');
        // 名字需要拆解最终转驼峰
        $tables = explode('.', $name);
        $table = $tables[0];
        $namespace = 'app\\model';
        $modelName = $tables[0];
        $modelDir = $tables[0];
        if(isset($tables[1])){
            $namespace .= '\\'.$tables[0];
            $modelName = $tables[1];
            $table = $tables[1];
            $connection = $tables[0];
            $modelDir .= DIRECTORY_SEPARATOR;
        }else{
            $modelDir = '';
        }

        self::check($connection, $table); // 检查模型对应的表是否存在

        $modelName = ucwords(Helper::camelize($modelName));

        $modelPath = App::appPath().DIRECTORY_SEPARATOR.'model'.DIRECTORY_SEPARATOR.$modelDir.$modelName.'.php';
        if(!file_exists($modelPath)){
            
            $code = self::getCode($namespace, $modelName, $table, $connection);
            // 如果目录不存在的话
            Helper::mkdirs(dirname($modelPath));
            file_put_contents($modelPath, $code); // 创建模型
            return $code;
        }
        return "";
    }

    /**
     * 获取创建初始化model的代码
     * @param string $namespace
     * @param string $modelName
     * @param string $table
     * @return string
     */
    protected static function getCode(string $namespace, string $modelName, string $table, string $connection){
        $code = Helper::headComment($modelName.'模型');
        $code .= "namespace $namespace;

use zhanshop\database\Model;

class $modelName extends Model
{
    // 设置当前模型的对应数据表
    protected \$table = '{$table}';

    // 设置当前模型的数据库连接
    protected \$connection = '{$connection}';
    
    // 设置当前模型软删除字段
    protected \$deleteTime = '';
    
}";
        return $code;
    }
}