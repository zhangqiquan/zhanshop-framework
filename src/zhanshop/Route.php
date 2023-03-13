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

class Route
{
    protected $version = "v1.0.0";
    /**
     * 注册的路由列表（请求类型,uri,方法,中间件）
     * @var array
     */
    protected $registers = [];

    public function __construct(){
        //echo "Route初始化\n";
    }

    public function setVersion(string $version){
        $this->version = $version;
    }
    /**
     * 注册路由
     * @param array $methods
     * @param string $uri
     * @param string $action
     * @param array $middleware
     */
    public function match(array $methods, string $uri, string $action = null){

        // 带$ 代表结束
        $this->registers[$this->version][$uri] = [$methods, $uri, $action];
    }

    public function uriPath(string &$uri) :string{
        $paths = explode("@", $uri);
        if(isset($paths[1])){
            // 把@后面的参数解析到参数
            //$param = explode('?', $paths[1]);
            parse_str($paths[1], $params);
            $_REQUEST = array_merge($_REQUEST, $params);
            return '/'.$paths[0];  // 不需要再解析?
        }else{
            //$paths = explode("?", $paths[0]);
            return '/'.$paths[0];
        }
    }
    // 这里有待优化
    public function swooleCallback(string $uri, $method, $protocol = 'http'){
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
        $routeInfo = $this->registers[$version][$uri] ?? App::error()->setError('您访问的接口不存在', 404);

        if(!in_array($method, $routeInfo[0])) App::error()->setError('当前接口不支持'.$method.'请求', 403);

        $actions = explode('@', $routeInfo[2]);

        $version = str_replace('.', '_', $version);
        $controller = '\app\\'.$protocol.'\\'.$version.'\controller\\'.$actions[0];

        return [
            'controller' => $controller,
            'service' => '\app\\'.$protocol.'\\'.$version.'\service\\'.$actions[0].'Service',
            'action' => $actions[1],
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
     * 获取全部路由
     * @return array
     */
    public function getAll($version = null){
        if($version) return $this->registers[$version];
        return $this->registers;
    }

    /**
     * 清空路由
     */
    public function clean(){
        $this->registers = [];
    }
}