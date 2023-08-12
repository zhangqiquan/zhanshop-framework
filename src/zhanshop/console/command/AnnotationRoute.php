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
                    $versionRouteCode .= "App::route()->group(\"/{$group}\", function (){".PHP_EOL;
                    // 拿到当前分组的所有中间件
                    $middlewares = [];
                    foreach($routes as $route){
                        foreach($route as $rowRoute) {
                            $middlewares = array_merge($middlewares, $rowRoute['middleware']);
                        }
                    }
                    $middlewares = array_unique($middlewares); // 拿到当前分组的所有中间件

                    // 排除那些不在分组内都存在的中间件
                    foreach($middlewares as $mk => $middleware){
                        foreach($routes as $route){
                            foreach($route as $rowRoute) {
                                if(!in_array($middleware, $rowRoute['middleware'])){
                                    unset($middlewares[$mk]);
                                }
                            }
                        }
                    }

                    // 排除那些路由中的中间件在全局中的中间件
                    foreach($routes as $uri => $route){
                        foreach($route as $rowKey => $rowRoute) {
                            foreach ($rowRoute['middleware'] as $mk => $middleware){
                                if(in_array($middleware, $middlewares)){
                                    unset($routes[$uri][$rowKey]['middleware'][$mk]);
                                }
                            }
                        }
                    }

                    foreach($routes as $route){
                        foreach($route as $rowKey => $rowRoute) {
                            $uri = explode('/', $rowRoute['uri'])[0];
                            $class = $rowRoute['handler'][0];
                            $action = $rowRoute['handler'][1];
                            $versionRouteCode .= "      App::route()->rule(\"".$rowRoute['method']."\", \"".$uri."\", [\\".$class."::class, \"".$action."\"])";
                            if($rowRoute['extra']){
                                $versionRouteCode .= '->extra('.json_encode($rowRoute['extra']).')';
                            }
                            if($rowRoute['validate']){
                                $versionRouteCode .= '->validate(['.'\\'.$rowRoute['validate'].'::class])';
                            }
                            if($rowRoute['middleware']){
                                $middleware = '['.implode(', ', array_values($rowRoute['middleware'])).']';
                                $versionRouteCode .= '->middleware('.$middleware.')';
                            }
                            $versionRouteCode .= ';'.PHP_EOL;
                        }
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
        $allDos = $model->table('apidoc')->column('id', 'id');
        foreach ($this->versionRoutes as $app => $versionRoutes){
            $routeDir = App::routePath().DIRECTORY_SEPARATOR.$app;
            // 相同路由请求方式不一样的中间件必须一致
            foreach ($versionRoutes as $version => $groupRoute){
                $versionRouteCode = Helper::headComment($app.'/'.$version);
                $versionRouteCode .= 'use zhanshop\App;'.PHP_EOL.PHP_EOL;
                foreach($groupRoute as $group => $routes){
                    foreach($routes as $route){
                        foreach($route as $rowRoute){
                            $param = [];
                            if($rowRoute['validate']){
                                $validate = App::make($rowRoute['validate']);
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
                                'version' => str_replace('_', '.', $version),
                                'uri' => $group.'.'.$rowRoute['uri'],
                                'method' => $rowRoute['method'],
                                'title' => $rowRoute['title'],
                                'groupname' => $rowRoute['group'],
                                'header' => json_encode($rowRoute['header'] ?? [], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE),
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
                                unset($allDos[$row['id']]);
                            }else{
                                // 插入
                                $model->table('apidoc')->insert($insetData);
                            }
                        }

                    }
                }
            }
        }

        $model->table('apidoc')->whereIn('id', $allDos)->delete(); // 删除已经被处理的文档
    }

    protected function generateClass(string $class)
    {
        $routes = [];
        try {
            $reflection= new \ReflectionClass($class);
            foreach($reflection->getMethods() as $method){
                $route = $this->generateMethod($method);
                if($route){
                    $routes[$route['uri']][] = $route;
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
            //$this->versionOriginalRoutes[$app][$version][$prefix] = $originalRoutes;
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
                    'method' => strtoupper($method),
                    'handler' => [$this->method->class, $this->method->name],
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


