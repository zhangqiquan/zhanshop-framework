<?php
// +----------------------------------------------------------------------
// | flow-course / Common.php    [ 2021/10/29 5:58 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace zhanshop\console\command\apicreate;



use zhanshop\Helper;

abstract class Common
{

    protected string $appName;

    protected string $version;

    protected string $className;

    protected array $methods;

    protected string $action;

    protected string $title;

    protected string $groupName;

    public function __construct(string $appName, string $version, string $className, array $methods, string $action, string $title, string $groupName)
    {
        $this->appName = $appName;
        $this->version = str_replace('.', '_', $version);
        $this->className = $className;
        $this->methods = $methods;
        $this->action = $action;
        $this->title = $title;
        $this->groupName = $groupName;
    }
    /**
     * 写入代码方法代码
     * @param string $classFile
     * @param string $code
     */
    private function writeCode(string $classFile, string $code){
        $classCode = file_get_contents($classFile);
        while (true){
            $rightStr = substr($classCode, -1);
            if($rightStr == ' ' || $rightStr == "\n"){
                $classCode = rtrim($classCode);
            }else{
                break;
            }
        }
        $classCode = substr($classCode,0,strlen($classCode)-1);
        $classCode = $classCode.$code."\n}";
        file_put_contents($classFile, $classCode);
    }

    /**
     * 检查class文件是否存在并写入
     * @param string $version
     * @param string $class
     * @param string $method
     */
    public function check(string $classFile){

        if(!file_exists($classFile)){
            echo PHP_EOL."【ok】构建".$classFile.'文件'.PHP_EOL;
            Helper::mkdirs(dirname($classFile)); // 创建class目录
            $this->createClassFile($classFile); // 创建class文件
        }
    }

    /**
     * 创建类方法
     * @param string $classFile
     */
    protected function createClassMethod(string $classFile, string $title){
        $methods = $this->getClassMethods($classFile);
        foreach($methods as $v){
            $code = $this->getClassMethodInitCode($v);
            $this->writeCode($classFile, $code); // 写入代码
            echo PHP_EOL.'【ok】构建'.$classFile.'文件'.PHP_EOL;
        }
    }

    /**
     * 创建控制器代码
     * @param string $version
     * @param string $class
     * @param string|null $code
     */
    protected function createClassFile(string $classFile, ?string $code = null){
        file_put_contents($classFile, $code ?? $this->getClassInitCode());
    }

    /**
     * 获取创建的方法名列表
     * @return mixed
     */
    abstract protected function getClassMethods(string $classFile) :array;

    /**
     * 获取初始化class代码
     * @return mixed
     */
    abstract protected function getClassInitCode();

    /**
     * 获取初始化class方法代码
     * @return mixed
     */
    abstract protected function getClassMethodInitCode($method);

}
