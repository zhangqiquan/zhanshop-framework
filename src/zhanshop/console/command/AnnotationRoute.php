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
use zhanshop\Helper;
use zhanshop\Log;

class AnnotationRoute extends Command
{

    public function configure()
    {
        $this->setTitle('注解生成路由')->setDescription('一键生成基于控制器配置的注解路由');
    }

    protected $versionRoutes = [];

    public function execute(Input $input, Output $output)
    {
        $apiDir = App::appPath() . DIRECTORY_SEPARATOR . 'api';
        $controllerFiles = glob($apiDir . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . '*.php');
        foreach ($controllerFiles as $k => $v) {
            $class = str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], str_replace(App::rootPath(), '', $v));
            $this->generateClass($class);
        }
        //print_r($this->versionRoutes);die;
        $this->write();

    }

    public function write(){
        foreach ($this->versionRoutes as $app => $versionRoutes){
            $routeDir = App::routePath().DIRECTORY_SEPARATOR.$app;
            Helper::mkdirs($routeDir);
            // 相同路由请求方式不一样的中间件必须一致
            foreach ($versionRoutes as $version => $groupRoute){
                $versionRouteCode = Helper::headComment($app.'/'.$version);
                foreach($groupRoute as $group => $routes){
                    $versionRouteCode .= "App::route()->group('/{$group}', function (){".PHP_EOL;
                    // 拿到相同的中间件
                    $middlewareTmps = array_column($routes, 'middleware');
                    $middlewares = [];
                    foreach($middlewareTmps as $middleware){
                        $middlewares = array_unique(array_merge($middlewares, $middleware));
                    }

                    foreach($middlewares as $mk => $middleware){
                        foreach($routes as $uri => $route){
                            if(!in_array($middleware, $route['middleware'])){
                                unset($middlewares[$mk]);
                            }
                        }
                    }
                    foreach($routes as $uri => $route){
                        foreach ($route['middleware'] as $mk => $middleware){
                            var_dump($middleware, $middlewares);
                            if(in_array($middleware, $middlewares)){
                                unset($routes[$uri]['middleware'][$mk]);
                            }
                        }
                    }

                    foreach($routes as $route){
                        $uri = explode('/', $route['uri'])[0];
                        $class = $route['handler'][0];
                        $action = $route['handler'][1];
                        $versionRouteCode .= "      App::route()->match(".json_encode($route['method']).", '.".$uri."', [".$class."::class, '".$action."'])";
                        if($route['extra']){
                            $versionRouteCode .= '->extra('.json_encode($route['extra']).')';
                        }
                        if($route['middleware']){
                            $versionRouteCode .= '->middleware('.json_encode($route['middleware']).')';
                        }
                        $versionRouteCode .= ';'.PHP_EOL;
                    }
                    $versionRouteCode .= '})';
                    if($middlewares){
                        $versionRouteCode .= "->middleware(".json_encode($middlewares).")";
                    }
                    $versionRouteCode .= ';'.PHP_EOL.PHP_EOL; // 全局中间件加进去
                }
                print_r($versionRouteCode);die;
                //App::route()->group('/index', function (){

                //$versionRouteCode .= 'App::route()->group('/index', function (){';
                var_dump($version);
                print_r($groupRoute);
            }
            //$routeCode = "<?php\n".Helper::headComment($k.'/'.);


        }
    }

    protected function generateClass(string $class)
    {
        $routes = [];
        try {
            $reflection= new \ReflectionClass($class);
            foreach($reflection->getMethods() as $method){
                $route = $this->generateMethod($method);
                if($route){
                    if(isset($routes[$route['uri']])){
                        $method = array_merge($routes[$route['uri']]['method'], $route['method']);
                        $uniqueMethod = array_unique($method);
                        if(count($method) != count($uniqueMethod)) App::error()->setError(print_r($route, true).'路由存在重复定义');
                        $routes[$route['uri']]['method'] = $method;
                    }else{
                        $routes[$route['uri']] = $route;
                    }

                }
            }
        }catch (\Throwable $exception){
            Log::errorLog(SWOOLE_LOG_ERROR,  $exception->getMessage());
        }

        if($routes){
            $classPath = explode('\\', $class);
            $app = $classPath[3] ?? '';
            if($app == false){
                Log::errorLog(SWOOLE_LOG_ERROR,  $class.'中无法确定app');
                exit();
            }
            $version = $classPath[4] ?? '';
            if($version == false){
                Log::errorLog(SWOOLE_LOG_ERROR,  $class.'中无法确定版本号');
                exit();
            }
            $prefix = lcfirst($classPath[count($classPath) - 1]);
            $this->versionRoutes[$app][$version][$prefix] = $routes;
        }
    }

    /**
     * 生成控制器方法路由
     * @param $method
     * @return array|false
     */
    protected function generateMethod($method){
        $docComment = $method->getDocComment();
        if($docComment == false) return false;
        $annotation = new Annotation($method,  $docComment);
        $route = $annotation->route();
        if($route){
            $route['middleware'] = $annotation->middleware();
            $route['title'] = $annotation->title();
            $route['group'] = $annotation->group();
            $route['validate'] = $annotation->validate();
            // 对进行相同的进行分组
            return $route;
        }
        return [];
    }
}

class Annotation{
    protected $method;
    protected  string $notes;
    protected $route = [];
    public function __construct($method, string $notes,)
    {
        $this->method = $method;
        $this->notes = str_replace(['\'', '"', '#'], '', $notes);
    }

    public function title(){
        $arr = explode("\n", $this->notes);
        if(isset($arr[1])){
            return str_replace('*', '', preg_replace('/\s+/',  '', $arr[1]));
        }
        return $this->method->name;
    }

    public function group(){
        $arr = explode("\n", $this->notes);
        $prefix = '@ApiGroup';
        foreach ($arr as $k => $v){
            if(strpos($v, $prefix) !== false){
                $route = str_replace(['*', $prefix], '', $v);
                return str_replace(' ', '', $route);
            }
        }
        return "未定义组";
    }

    public function route(){
        $arr = explode("\n", $this->notes);
        $prefix = '@Route(';
        foreach ($arr as $k => $v){
            if(strpos($v, $prefix) !== false){
                $route = str_replace(['*', $prefix, ')'], '', $v);
                $arr = explode(', ', $route);
                // 这里的方法只能有一个
                $method = str_replace(' ', '', $arr[1] ?? 'GET');
                $method = strtolower($method);
                if(strpos($this->method->name, $method) !== 0){
                    Log::errorLog(SWOOLE_LOG_ERROR,  $this->method->class.'->'.$this->method->name.' Route注解指定的是'.$method.'而方法却是'.$this->method->name.'前缀不一致');
                    die;
                }

                $uri = str_replace(' ', '', $arr[0]);
                $uris = explode('/', $uri);
                $extras = [];
                if(count($uris) > 1){
                    unset($uris[0]);
                    foreach($uris as $extra){
                        $extras[] = str_replace(['{', '}'], '', $extra);
                    }
                }
                return [
                    'uri' => $uri,
                    'method' => [strtoupper($method)],
                    'handler' => [$this->method->class, lcfirst(substr($this->method->name, strlen($method), 9999))],
                    'extra' => $extras
                ];
            }
        }

        return [];

    }

    public function middleware(){
        $arr = explode("\n", $this->notes);
        $prefix = '@Middleware(';
        foreach ($arr as $k => $v){
            if(strpos($v, $prefix) !== false){
                $route = str_replace(['*', $prefix, ')'], '', $v);
                $arr = explode(', ', $route);
                $middleware = [];
                foreach($arr as $k => $v){
                    $class = str_replace(' ', '', $v);
                    $middleware[] = $class;
                }
                return $middleware;
            }
        }

        return [];
    }

    public function validate(){
        $class = str_replace('\\controller\\', '\\validate\\', $this->method->class.ucfirst($this->method->name));
        $classPath = App::rootPath().str_replace('\\',  DIRECTORY_SEPARATOR, $class).'.php';
        if(file_exists($classPath)){
            return $class;
        }
        return null;
    }

    // 响应说明放在编辑里面
}


