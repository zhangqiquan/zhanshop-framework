<?php
// +----------------------------------------------------------------------
// | framework / Route.php    [ 2021/10/30 10:08 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2022 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop;

use zhanshop\route\Group;
use zhanshop\route\Rule;

class Route
{
    /**
     * 路由规则
     * @var Rule
     */
    protected Rule $rule;
    protected Group $group;

    public function __construct(){
        $this->rule = new Rule();
        $this->group = new Group();
        $this->rule->setGroup($this->group);
    }

    /**
     * 获取rule对象
     * @return Rule
     */
    public function getRule(){
        return $this->rule;
    }

    /**
     * 注册路由
     * @param array $methods
     * @param string $uri
     * @param string $action
     * @param array $middleware
     */
    public function match(array $methods, string $uri, array $handler): Rule{
        return $this->rule->addRule($uri, $handler, $methods);
    }


    /**
     * 分组路由
     * @param string $name
     * @param callable $fun
     * @return void
     */
    public function group(string $name, callable $fun): Group{
        $group = new Group();
        $group->addGroup($name, $fun);
        return $group;
    }

    public function extra(string $str){

    }

    public function uriPath(string &$uri) :string{
        $paths = explode("@", $uri);
        if(isset($paths[1])){
            // 把@后面的参数解析到参数
            //$param = explode('?', $paths[1]);
            parse_str($paths[1], $params);
            return '/'.$paths[0];  // 不需要再解析?
        }else{
            //$paths = explode("?", $paths[0]);
            return '/'.$paths[0];
        }
    }


    /**
     * 解析路由
     * @param string $uri
     * @param $method
     * @param $protocol
     * @return array
     * @throws \Exception
     */
    public function parse(string $uri, string $method, string $group = 'http'){
        $arr = explode("/", $uri);
        $version = $arr[1];
        if($version == false){
            $version = "v1.0.0";
        }

        $uri = '/';
        if(isset($arr[2])){
            // 如果有额外参数
            $uri = $arr[2];
            if(strpos($uri, '@') !== false){
                $uri = $this->uriPath($uri);
            }else{
                $uri = '/'.$uri;
            }
        }
        $routeInfo = $this->registers[$group][$version][$uri] ?? App::error()->setError('您访问的API不存在', Error::NOT_FOUND);

        if(!in_array($method, $routeInfo[0])) App::error()->setError('当前API不支持'.$method.'请求', Error::FORBIDDEN);

        $controller = $routeInfo[2][0];
        return [
            'controller' => $controller,
            'service' => str_replace('\\controller\\', '\\service\\', $controller).'Service',
            'action' => $routeInfo[2][1],
        ];
    }

    /**
     * 获取路由
     * @param string $uri
     * @return array|mixed
     */
    public function get($version, string $uri){
        return $this->registers[$version][$uri] ?? App::error()->setError('您访问的接口不存在', 404);
    }

    /**
     * 清空路由
     */
    public function clean(){
        $this->registers = [];
        $this->grpcService = [];
        $this->jsonRpcService = [];
    }

    protected $grpcService = [];
    /**
     * 注册grpc服务
     * @param $class
     * @return void
     */
    public function setGrpc(string $uri, string $class){
        $this->grpcService[$uri] = [
            'class' => $class
        ];
        // 通过反射拿到请求类和响应类
        $reflectionClass = new \ReflectionClass($class);
        $methods = $reflectionClass->getMethods();
        foreach($methods as $v){
            $method = $v->getName();
            $parameters = $reflectionClass->getMethod($method)->getParameters();
            if(isset($parameters[0]) && $parameters[1]){
                $this->grpcService[$uri]['method'][$method][] = $parameters[0]->getType()->getName();
                $this->grpcService[$uri]['method'][$method][] = $parameters[1]->getType()->getName();
            }
        }
    }

    public function getGrpc(string $uri, string $method){
        $service = $this->grpcService[$uri] ?? App::error()->setError('您所请求的资源不存在', Error::NOT_FOUND);
        return [
            'service' => $service['class'],
            'param' => $service['method'][$method] ?? App::error()->setError('您所请求的方法'.$method.'不存在', Error::NOT_FOUND),
        ];
    }

    protected $jsonRpcService = [];
    /**
     * 注册jsonRpc
     * @param $class
     * @return void
     * @throws \ReflectionException
     */
    public function setJsonRpc(string $uri, string $class){
        $this->jsonRpcService[$uri] = [
            'class' => $class
        ];
        // 通过反射拿到请求类和响应类
        $reflectionClass = new \ReflectionClass($class);
        $methods = $reflectionClass->getMethods();
        foreach($methods as $v){
            $method = $v->getName();
            $parameters = $reflectionClass->getMethod($method)->getParameters();
            if(isset($parameters[0]) && $parameters[1]){
                $this->jsonRpcService[$uri]['method'][$method][] = $parameters[0]->getType()->getName();
                $this->jsonRpcService[$uri]['method'][$method][] = $parameters[1]->getType()->getName();
            }
        }
    }

    public function getJsonRpc(string $uri, string $method){
        $service = $this->jsonRpcService[$uri] ?? App::error()->setError('您所请求的资源不存在', Error::NOT_FOUND);
        return [
            'service' => $service['class'],
            'param' => $service['method'][$method] ?? App::error()->setError('您所请求的方法'.$method.'不存在', Error::NOT_FOUND),
        ];
    }

    /**
     * 获取所有路由
     * @return array
     */
    public function getAll(){
        return $this->rule->getAll();
    }
}