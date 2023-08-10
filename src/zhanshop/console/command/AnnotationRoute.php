<?php
// +----------------------------------------------------------------------
// | flow-course / Help.php    [ 2021/10/28 2:26 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\console\command;

use zhanshop\App;
use zhanshop\console\Command;
use zhanshop\console\Input;
use zhanshop\console\Output;
use zhanshop\Log;

class AnnotationRoute extends Command
{

    public function configure()
    {
        $this->setTitle('注解生成路由')->setDescription('一键生成基于控制器配置的注解路由');
    }

    public function execute(Input $input, Output $output)
    {
        $apiDir = App::appPath() . DIRECTORY_SEPARATOR . 'api';
        $controllerFiles = glob($apiDir . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . '*.php');
        foreach ($controllerFiles as $k => $v) {
            $class = str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], str_replace(App::rootPath(), '', $v));
            $this->generate($class);
        }
    }
    protected    $groupRoute = [];
    protected    $route = [];
    protected function generate(string $class)
    {
        $this->groupRoute = [];
        try {
            $reflection= new \ReflectionClass($class);
            foreach($reflection->getMethods() as $method){
                $this->generateMethod($method);
            }
        }catch (\Throwable $exception){
                Log::errorLog(SWOOLE_LOG_ERROR,  $exception->getMessage());
        }
    }

    protected function generateMethod($method){
        var_dump($method->getDocComment());
                $annotation = new Annotation($method, $method->getDocComment());
        $annotation->route();
        //反射
        $m = new ReflectionMethod($this, $name);

        //方法注释
        $note = mid(trim($m . ''), '/**', '*/');

        //取指定标签 funcName 的值
        $matched = preg_match('/@funcName\s*([^\s]*)/i', $note, $matches);
        $this->route[] = [
        ];
        print_r($method);
    }
}

class Annotation{
    protected $method;
    protected  string $notes;
    protected $route = [];
    public function __construct($method, string $notes,)
    {
        $this->method = $method;
        $this->notes = $notes;
    }

    public function title(){

    }

    public function route(){
        $arr = explode("\n", $this->notes);
        $prefix = '@Route(';
        foreach ($arr as $k => $v){
            if(strpos($v, '@Route(')){
                $route = str_replace(['*', $prefix, ')'], '', $v);
                var_dump($route);
                $arr = explode(', ', $route);
                                if($arr == false){
                                        $this->route['uri'] = str_replace(' ', '', $arr[0]);
                                    $this->route['methods'] = str_replace(' ', '', $arr[1]);
                                    $this->route['handler'] = [$this->method];
                                    $this->route['extra'] = str_replace(' ', '', $arr[2]);
                                    $this->route['cross_domain'] = $arr[1];
                                    $this->route['middleware'] = $arr[1];
                                    $this->route['cache'] = $arr['cache'];
                                    /**
                                     * 'methods' => $methods,
                                     * 'handler' => $handler,
                                     * 'service' => [str_replace('\\controller\\', '\\service\\', $handler[0]).'Service', ucfirst($handler[1])],
                                     * 'middleware' => array_merge($this->currentGroup->getMiddleware(), $this->globalMiddleware),
                                     * 'cache' => $this->currentGroup->getCache(),
                                     * 'extra' => [],
                                     * 'cross_domain' => $this->currentGroup->getCrossDomain()
                                     */
                                }
                var_dump($arr);
            }
        }

                Log::errorLog(SWOOLE_LOG_WARNING,   $this->method->class.'->'.$this->method->name.'没有匹配到@Route注解');
    }

    public function middleware(){

    }

    public function crossDomain(){

    }

    public function validate(){

    }

    // 响应说明放在编辑里面
}


