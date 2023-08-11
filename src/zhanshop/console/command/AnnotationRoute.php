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

use zhanshop\apidoc\ApiDocModel;
use zhanshop\apidoc\ApiDocService;
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
        $this->apiDoc();
    }

    public function write(){
        foreach ($this->versionRoutes as $app => $versionRoutes){
            $routeDir = App::routePath().DIRECTORY_SEPARATOR.$app;
            Helper::mkdirs($routeDir);
            // 相同路由请求方式不一样的中间件必须一致
            foreach ($versionRoutes as $version => $groupRoute){
                $versionRouteCode = Helper::headComment($app.'/'.$version);
                $versionRouteCode .= 'use zhanshop\App;'.PHP_EOL.PHP_EOL;
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
                            if(in_array($middleware, $middlewares)){
                                unset($routes[$uri]['middleware'][$mk]);
                            }
                        }
                    }

                    foreach($routes as $route){
                        $uri = explode('/', $route['uri'])[0];
                        $class = $route['handler'][0];
                        $action = $route['handler'][1];
                        $versionRouteCode .= "      App::route()->match(".json_encode($route['method']).", \"".$uri."\", [\\".$class."::class, '".$action."'])";
                        if($route['extra']){
                            $versionRouteCode .= '->extra('.json_encode($route['extra']).')';
                        }
                        if($route['validate']){
                            $versionRouteCode .= '->validate(['.'\\'.$route['validate'].'::class])';
                        }
                        if($route['middleware']){
                            $middleware = '['.implode(', ', array_values($route['middleware'])).']';
                            $versionRouteCode .= '->middleware('.$middleware.')';
                        }
                        $versionRouteCode .= ';'.PHP_EOL;
                    }
                    $versionRouteCode .= '})';
                    if($middlewares){
                        $middlewares = '['.implode(', ', array_values($middlewares)).']';
                        $versionRouteCode .= "->middleware(".$middlewares.")";
                    }
                    $versionRouteCode .= ';'.PHP_EOL.PHP_EOL; // 全局中间件加进去
                }
                file_put_contents($routeDir.DIRECTORY_SEPARATOR.str_replace('_', '.', $version).'.php', $versionRouteCode);
            }
        }
    }

    // 生成apiDoc
    public function apiDoc(){
        $model = (new ApiDocModel())->getQuery();
        foreach ($this->versionRoutes as $app => $versionRoutes){
            $routeDir = App::routePath().DIRECTORY_SEPARATOR.$app;
            // 相同路由请求方式不一样的中间件必须一致
            foreach ($versionRoutes as $version => $groupRoute){
                $versionRouteCode = Helper::headComment($app.'/'.$version);
                $versionRouteCode .= 'use zhanshop\App;'.PHP_EOL.PHP_EOL;
                foreach($groupRoute as $group => $routes){

                    foreach($routes as $route){
                        $param = [];
                        if($route['validate']){
                            $validate = App::make($route['validate']);
                            foreach($validate->rule as $field => $rule){
                                $param[$field] = [
                                    'rule' => $rule,
                                    'title' => $validate->message[$field] ?? $field,
                                    'description' => $validate->description[$field] ?? null,
                                ];
                            }
                        }
                        $insetData = [
                            'protocol' => 'http',
                            'app' => $app,
                            'version' => $version,
                            'uri' => $group.'.'.$route['uri'],
                            'method' => $route['method'][0],
                            'title' => $route['title'],
                            'groupname' => $route['group'],
                            'header' => json_encode($route['header'] ?? [], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE),
                            'param' => json_encode($param, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE)
                        ];

                        $row = $model->table('apidoc')->where([
                            'protocol' => $insetData['protocol'],
                            'app' => $insetData['app'],
                            'version' => $insetData['version'],
                            'uri' => $insetData['uri'],
                            'method' => $insetData['method'],
                        ])->find();
                        if($row){
                            // 更新
                            $model->table('apidoc')->where(['id' => $row['id']])->update($insetData);
                        }else{
                            // 插入
                            $model->table('apidoc')->insert($insetData);
                        }
                    }
                }
            }
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

                        $middleware = array_merge($routes[$route['uri']]['middleware'], $route['middleware']);
                        $uniqueMiddleware = array_unique($middleware);
                        $routes[$route['uri']]['middleware'] = $uniqueMiddleware;
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
            $route['header'] = $annotation->header();
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
    // 中间件支持多个
    public function middleware(){
        $middlewarePath = App::appPath().DIRECTORY_SEPARATOR.'middleware'.DIRECTORY_SEPARATOR;
        $arr = explode("\n", $this->notes);
        $prefix = '@Middleware(';
        foreach ($arr as $k => $v){
            if(strpos($v, $prefix) !== false){
                $route = str_replace(['*', $prefix, ')'], '', $v);
                $arr = explode(', ', $route);
                $middleware = [];
                foreach($arr as $k => $v){
                    $class = str_replace(' ', '', $v);
                    // 检查中间件是否存在
                    $file = $middlewarePath.str_replace(['\\', '::class'], [DIRECTORY_SEPARATOR, '.php'], $class);
                    if(!file_exists($file)){
                        Log::errorLog(SWOOLE_LOG_ERROR,  $this->method->class.'->'.$this->method->name.' Middleware注解指定 '.$class.' 中间件未定义');
                        die;
                    }
                    $middleware[] = '\\app\\middleware\\'.str_replace('/', '\\', $class);
                }
                return $middleware;
            }
        }

        return [];
    }

    public function validate(){
        $class = str_replace('\\controller\\', '\\validate\\', $this->method->class.ucfirst($this->method->name));
        $classPath = App::rootPath().DIRECTORY_SEPARATOR.str_replace('\\',  DIRECTORY_SEPARATOR, $class).'.php';
        if(file_exists($classPath)){
            return $class;
        }
        return null;
    }

    public function header(){
        $middlewarePath = App::appPath().DIRECTORY_SEPARATOR.'middleware'.DIRECTORY_SEPARATOR;
        $arr = explode("\n", $this->notes);
        $prefix = '@Header(';
        foreach ($arr as $k => $v){
            if(strpos($v, $prefix) !== false){
                $route = str_replace(['*', $prefix, ')'], '', $v);
                $arr = explode(', ', $route);
                $headers = [];
                foreach($arr as $k => $v){
                    $header = str_replace(' ', '', $v); //
                    $header = explode('=', $header);
                    $val = $header[1] ?? null;
                    if($val == false){
                        Log::errorLog(SWOOLE_LOG_ERROR,  $this->method->class.'->'.$this->method->name.' Header注解指定 '.$header[0].'后面应该包含一个=字段说明');
                        die;
                    }
                    $headers[$header[0]] = $val;
                }
                return $headers;
            }
        }

        return [];
    }

    // 响应说明放在编辑里面
}


