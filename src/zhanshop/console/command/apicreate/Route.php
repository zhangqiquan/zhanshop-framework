<?php
// +----------------------------------------------------------------------
// | flow-course / Route.php    [ 2021/10/29 2:46 下午 ]
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

class Route
{
    protected string $appName;

    protected string $version;

    protected string $uri;

    public function __construct($appName, $version, $uri, $middleware)
    {
        $this->appName = $appName;
        $this->version = $version;
        $this->uri = $uri;
        App::route()->getRule()->setApp($appName, $version, $middleware); // 设置当前路由版本信息
    }

    public static function create(Input $input, string $appName){
        $middlewares = App::config()->get('middleware', []); // 检查是否有全局中间件

        $self = new static($appName, $input->param('version'), $input->param('uri'), $middlewares[$appName] ?? []);

        $self->check(); // 检查路由是否已经存在存在提示不可重复定义

        // 注册路由
        App::route()->match($input->param('method'), $input->param('uri'), [$input->param('class'), $input->param('action')]);

        // 写路由文件
        $self->write();

        echo PHP_EOL."路由生成成功".PHP_EOL;
    }

    /**
     * 检查这个路由是否存在
     * @return void
     */
    public function check(){

        $routeFile = App::rootPath() .DIRECTORY_SEPARATOR. 'route' . DIRECTORY_SEPARATOR. $this->appName .DIRECTORY_SEPARATOR. $this->version . '.php';
        if(!file_exists($routeFile)) $this->createRouteFile($this->version);

        include $routeFile;
        $routes = App::route()->getAll();
        if(isset($routes[$this->version][$this->uri])){
            echo "???【警告！当前输入的uri已经存在】???";
        }
    }

    /**
     * 重写路由文件
     */
    public function write(){
        // 对路由进行排序
        $all = App::route()->getAll()[$this->appName];
        $all[$this->version] = $all[$this->version] ?? [];
        if($all[$this->version]) ksort($all[$this->version]);
        $fileData = Helper::headComment('路由文件')."use zhanshop\App;\n\n";
        foreach($all[$this->version] as $k => $v){
            $methods = $v['methods'];
            $handler = $v['handler'];
            $handler[0] = ltrim($handler[0], '\\');
            $handler[0] = '\\'.$handler[0];
            $method = strtoupper("'".implode("','", $methods)."'");

            $fileData .= "App::route()->match([".$method."], '".$k."', [".$handler[0].'::class, '."'".$handler[1]."'"."]);\n";
        }
        // 写新的有问题老的没有问题
        $this->createRouteFile($fileData); // 写入路由文件
    }

    /**
     * 写入路由文件
     * @param string $version
     * @param string|null $code
     */
    public function createRouteFile(?string $code = null){
        file_put_contents(App::rootPath().DIRECTORY_SEPARATOR.'route'.DIRECTORY_SEPARATOR. $this->appName .DIRECTORY_SEPARATOR.$this->version.'.php', $code ?? '<?php'.PHP_EOL);
    }
}
