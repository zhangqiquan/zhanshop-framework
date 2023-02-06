<?php
// +----------------------------------------------------------------------
// | flow-course / Model.php    [ 2021/10/29 2:46 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace zhanshop\console\command\apicreate;


use zhanshop\App;
use zhanshop\console\Input;
use zhanshop\Helper;

class Model extends Common
{
    protected static $input = null;

    protected static $modelName = null;

    public static function create(Input $input){
        // 如果表存在的话
        if($input->param('table')){
            self::$input = $input;
            self::$modelName = ucwords(Helper::camelize($input->param('table')));
            $classFile = App::appPath().DIRECTORY_SEPARATOR.'model'.DIRECTORY_SEPARATOR.self::$modelName.'.php';

            self::check($classFile); // 检查控制器是否存在不存在初始化
        }
    }

    /**
     * 获取model初始化代码
     */
    protected static function getClassInitCode(){
        $class = self::$input->param('class');
        $code = Helper::headComment(self::$modelName);
        $modelName = self::$modelName;
        $table = self::$input->param('table');
        $code .= "
namespace app\\model;

use zhanshop\database\Model;

class $modelName extends Model
{
    // 设置当前模型的对应数据表
    protected \$table = '{$table}';

    // 设置当前模型的数据库连接
    protected \$connection = 'mysql';
    
}";
        return $code;
    }

    protected static function getClassMethods(string $classFile): array
    {
        // TODO: Implement getClassMethods() method.
    }

    protected static function getClassMethodInitCode(string $title, string $method)
    {
        // TODO: Implement getClassMethodInitCode() method.
    }
}