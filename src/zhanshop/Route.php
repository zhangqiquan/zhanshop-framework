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
    }

    /**
     * 获取rule对象
     * @return Rule
     */
    public function getRule(){
        return $this->rule;
    }

    /**
     * 注册路由规则
     * @param string $method
     * @param string $uri
     * @param array $handler
     * @return Rule
     */
    public function rule(string $method, string $uri, array $handler): Rule{
        return $this->rule->addRule($uri, $handler, $method);
    }


    /**
     * 分组路由
     * @param string $name
     * @param callable $fun
     * @return void
     */
    public function group(string $name, callable $fun){
        $this->group = new Group();
        $this->group->addGroup($name, $fun);
        // 释放掉之前的组
        return $this->group;
    }

    /**
     * 清空路由
     */
    public function clean(){
        $this->rule->clear();
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

    public function sortMiddleware(){
        return $this->rule->sortMiddleware();
    }
}