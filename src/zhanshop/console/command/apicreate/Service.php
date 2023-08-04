<?php
// +----------------------------------------------------------------------
// | flow-course / Service.php    [ 2021/10/29 2:46 下午 ]
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

class Service extends Common
{

    public static function create(Input $input, string $appName){

        $class = $input->param('class');
        $class = str_replace('\\', DIRECTORY_SEPARATOR, str_replace('\\controller\\', '\\service\\', $class));

        $classFile = App::rootPath().$class.'Service.php';

        $self = new static($appName, $input->param('version'), pathinfo($classFile, PATHINFO_FILENAME), $input->param('method'), $input->param('action'), $input->param('title'), $input->param('groupname'));


        $self->check($classFile); // 检查控制器是否存在不存在初始化

        $self->createClassMethod($classFile, $input->param('title'));
    }

    /**
     * 获取控制器初始化代码
     */
    protected function getInitClassCode(){
        $code = Helper::headComment($this->className.'Service');
        $code .= "
namespace app\\".($this->appName)."\\".$this->version."\\service;

use zhanshop\\App;
use zhanshop\Request;
use zhanshop\Response;

class {$class}Service
{
    
}";
        return $code;
    }

    /**
     * 获取需要创建的方法名列表
     * @param string $classFile
     * @return array
     * @throws \ReflectionException
     */
    protected function getClassMethods(string $classFile): array
    {
        $class = substr($classFile, strlen(App::rootPath()), 99999);
        $class = str_replace('/', '\\', rtrim($class, '.php'));
        $ref = new \ReflectionClass(new $class());

        $methods = json_decode(json_encode($ref->getMethods()), true);
        if(is_array($methods)) $methods = array_column($methods, 'name');

        $reqTypes =  $this->methods; // 请求方法

        $res = [];
        foreach($reqTypes as $v){
            $reqType = $v.ucwords($this->action); // 驼峰命名
            if(!in_array($reqType, $methods)){
                // 创建Service对象RestFul 方法
                $res[] = $reqType;
            }
        }
        return $res;
    }

    /**
     * 获取serviceClass初始化代码
     * @return string
     */
    protected function getClassInitCode()
    {
        $version = $this->version;
        $code = Helper::headComment($this->className.'Service');
        $code .= "
namespace app\\api\\".$this->appName."\\$version\service;

use zhanshop\\App;
use zhanshop\\Request;
use zhanshop\\Response;

class {$this->className}
{
    
}";
        return $code;
    }

    /**
     * 获取需要创建的方法初始化代码
     * @param string $title
     * @param string $method
     * @return mixed|void
     */
    protected function getClassMethodInitCode($method)
    {
        $curd = '[];';
        $code = "\t/**
     * {$this->title} $method 方法
     * @apiParam {String} Required param 示例参数
     * @apiExplain {json} {\"错误码\":\"错误描述\"}
     * @return array
     */
    public function $method(Request &\$request, Response &\$response){
        \$data = $curd
        return \$data;
    }";
        return $code;
    }
}
