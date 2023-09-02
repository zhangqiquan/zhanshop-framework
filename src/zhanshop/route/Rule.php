<?php
declare (strict_types = 1);

namespace zhanshop\route;

use zhanshop\App;
use zhanshop\Request;

/**
 * 路由分组类
 */
class Rule
{
    /**
     * 路由绑定
     * @var array
     */
    protected $bind = [];

    /**
     * 当前组
     * @var Group null
     */
    protected $currentGroup = null;
    /**
     * 当前appName
     * @var string
     */
    protected $currentAppName = '';

    /**
     * 当前AppVersion
     * @var string
     */
    protected $currentAppVersion = '';
    /**
     * 当前uri
     * @var string
     */
    protected $currentUri = '';

    /**
     * 当前请求方法
     * @var string
     */
    protected $currentMethod = 'GET';

    /**
     * 当前APP的全局中间件
     * @var array
     */
    protected array $globalMiddleware = [];

    /**
     * 设置当前路由所属app
     * @param string $name
     * @param string $version
     * @return void
     */
    public function setApp(string $name, string $version, array $middleware = []){
        $this->currentAppName = $name;
        $this->currentAppVersion = $version;

        foreach($middleware as $v){
            $this->globalMiddleware[] = function (Request &$request, \Closure &$next) use (&$v){
                App::make($v)->handle($request, $next);
            };
        }
    }

    public function setGroup(mixed &$group){
        $this->currentGroup = $group;
    }


    /**
     * 设置路由
     * @param array $methods
     * @param string $uri
     * @param array $handler
     * @return void
     */
    public function addRule(string $uri, array &$handler, string $method = "GET") :Rule{
        $prefix = $this->currentGroup->getPrefix();
        $this->currentUri = $prefix ? $prefix.'.'.$uri : $uri;
        $this->currentMethod = $method;
        //$handler[1] = ucfirst($handler[1]);
        $this->bind[$this->currentAppName][$this->currentAppVersion][$this->currentUri][$method] = [
            'method' => $method,
            'handler' => $handler,
            //'service' => [str_replace('\\controller\\', '\\service\\', $handler[0]).'Service', $handler[1]],
            'middleware' => [],//array_merge($this->currentGroup->getMiddleware(), $this->globalMiddleware),
            'cache' => $this->currentGroup->getCache(),
            'extra' => [],
            'validate' => [],
            'cross_domain' => $this->currentGroup->getCrossDomain()
        ];
        return $this; // 测试分组路由
    }

    /**
     * 额外参数
     * @param array $params
     * @return void
     */
    public function extra(array $params) :Rule{
        $this->bind[$this->currentAppName][$this->currentAppVersion][$this->currentUri][$this->currentMethod]['extra'] = $params;
        return $this;
    }

    /**
     * 设置中间件
     * @param array $class
     * @return void
     */
    public function middleware(array $class) :Rule{

//        foreach($class as $name){
//            $this->bind[$this->currentAppName][$this->currentAppVersion][$this->currentUri][$this->currentMethod]['middleware'][] = function (Request &$request, \Closure &$next) use (&$name){
//                App::make($name)->handle($request, $next);
//            };
//        };
        return $this;
    }

    /**
     * 清空分组下的路由规则
     * @access public
     * @return void
     */
    public function clear(): void
    {
        $this->bind = [];
    }

    /**
     * 获取已绑定的路由
     * @param $name
     * @param $version
     * @param $uri
     * @return array|mixed
     */
    public function getBind(string &$name, string &$version,  string &$uri, string &$method){
        return $this->bind[$name][$version][$uri][$method] ?? [];
    }

    /**
     * 获取所有路由
     * @return array
     */
    public function getAll(){
        return $this->bind;
    }

    public function sortMiddleware(){
//        foreach ($this->bind as $app => $versions){
//            foreach($versions as $version => $methods){
//                //var_dump($version);
//                foreach($methods as $uri => $routes){
//                    //var_dump($uri);
//                    //print_r($routes);
//                    foreach($routes as $method => $route){
//                        //var_dump($method);
//                        $route['middleware'] = array_reverse($route['middleware']);
//                        $this->bind[$app][$version][$uri][$method]['middleware'] = $route['middleware'];
////                        foreach($route['middleware'] as $v){
////                            echo substr(print_r($v, true),0, 200).PHP_EOL;
////                        }
////                        die;
//                        //print_r($route['middleware']);
//                    }
//                }
//            }
//            //print_r($v);
//        }
//        //die;
    }
}
