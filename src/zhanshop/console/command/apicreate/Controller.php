<?php
// +----------------------------------------------------------------------
// | zhanshop / Controller.php    [ 2021/10/29 2:45 下午 ]
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

class Controller extends Common
{
    /**
     * 创建控制器
     * @param Input $input
     */
    public static function create(Input $input, string $appName){
        $class = $input->param('class');
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);

        $classFile = App::rootPath().$class.'.php';

        $self = new static($appName, $input->param('version'), pathinfo($classFile, PATHINFO_FILENAME), $input->param('method'), $input->param('action'), $input->param('title'), $input->param('groupname'));
        $self->check($classFile); // 检查控制器是否存在不存在初始化

        $self->createClassMethod($classFile, $input->param('title'));
    }

    /**
     * 获取初始化控制器代码
     * @return string
     */
    protected function getClassInitCode()
    {
        $code = Helper::headComment($this->className.'控制器');
        $version = $this->version;
        $code .= "
namespace app\\api\\".($this->appName)."\\{$version}\controller;

use zhanshop\\Request;
use zhanshop\\Response;
use app\\api\\".($this->appName)."\\Controller;

class {$this->className} extends Controller
{
    
}";
        return $code;
    }

    /**
     * 获取初始化控制器方法代码
     * @return string
     */
    protected function getClassMethodInitCode($method)
    {
        $code = "
     /**
     * {$this->title}
     * @apiGroup {$this->groupName}
     * @return mixed
     */
    public function {$this->action}(Request &\$request, Response &\$response){
        return '{$this->action}生成方法';
    }";
        return $code;
    }

    /**
     * 获取需要创建的方法名列表
     * @return array
     */
    protected function getClassMethods(string $classFile): array
    {
        $method = $this->methods;
        $class = substr($classFile, strlen(App::rootPath()), 99999);
        $class = str_replace('/', '\\', rtrim($class, '.php'));
        $ref = new \ReflectionClass(new $class());

        $methods = json_decode(json_encode($ref->getMethods()), true);
        if(is_array($methods)) $methods = array_column($methods, 'name');

        $res = [];
        if(!in_array($method, $methods)){
            $res[] = $method;
        }
        return $res;
    }
}
