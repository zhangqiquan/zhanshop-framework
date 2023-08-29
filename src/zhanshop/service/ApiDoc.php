<?php
// +----------------------------------------------------------------------
// | framework / ApiDoc.php    [ 2023/8/18 14:38 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\service;

use zhanshop\App;
use zhanshop\Error;
use zhanshop\helper\Annotations;
use zhanshop\Request;
use zhanshop\Response;

class ApiDoc
{
    protected $app = "";
    protected $menuList = [];
    /**
     * 初始化
     * @param string $appName
     * @return void
     * @throws \Exception
     */
    public function init(string $app){
        if(($_SERVER['APP_ENV'] ?? 'dev') == 'production') App::error()->setError('访问的接口不存在', Error::NOT_FOUND);

        $this->app = $app;
        $menuFile = App::runtimePath().DIRECTORY_SEPARATOR.'apidoc'.DIRECTORY_SEPARATOR.$app.'-menu.json';
        if(!file_exists($menuFile)){
            App::error()->setError('apiDoc菜单还没有生成', Error::NOT_FOUND);
        }
        $this->menuList = json_decode(file_get_contents($menuFile), true);
    }

    /**
     * 获取菜单列表
     * @param Request $request
     * @param Response $response
     * @return array
     */
    public function get(Request &$request, Response &$response){
        return [
            'menu' => array_values($this->menuList),
            'user' => [
                "user_id" => 0,
                "user_name" => "游客",
                "avatar" => "http://test-cdn.zhanshop.cn/2023314/16787312296131333165.jpg"
            ]
        ];
    }

    /**
     * 调用
     * @param Request $request
     * @param Response $response
     * @return array
     * @throws \Exception
     */
    public function call(Request &$request, Response &$response){
        $this->init($request->getRoure()['extra'][0]);
        $method = strtolower($request->method());
        $data = $this->$method($request, $response);
        return [
            'code' => 0,
            'msg' => 'ok',
            'data' => $data,
        ];
    }

    /**
     * 获取api详情
     * @param $app
     * @param $protocol
     * @param $uri
     * @param $method
     * @param $version
     * @return array
     */
    public function detail(Request &$request, Response &$response){

        $uri = $request->param('uri');
        $version = explode('/', $uri)[0];
        $uri = '/'.substr($uri, strlen($version) + 1, 999);
        $versions = $this->menuList[$uri]['versions'] ?? [];
        $methods = App::route()->getAll()[$this->app][$version][$uri] ?? App::error()->setError($request->param('uri').'路由未注册', Error::NOT_FOUND);
        foreach($methods as $k => $v){
            $handler = $v['handler'];
            $class = new \ReflectionClass($handler[0]);
            $method = $class->getMethod($handler[1]);
            $apiDoc = (new Annotations($method->getDocComment()))->all();
            print_r($apiDoc);
        }
        print_r($methods);
        //App::route()->getRule()->getBind($this->app, $version, $uri, '');
    }

    public function samplecode(Request &$request, Response &$response){
        $app = $request->getRoure()['extra'][0];
        $data = $request->validateRule([
            'protocol' => 'required',
            'version' => 'string',
            'uri' => 'required',
            'method' => 'required',
            'language' => 'required',
        ])->getData();
        $language = $data['language'];
        $apiDoc = App::make(ApiDocService::class)->detail($app, $data['protocol'], $data['uri'], $data['version'] ?? "", $data['method'])['detail'][0];
        $code = ApiSampleCode::$language($request->header('origin').'/'.$apiDoc['version'].'/'.$apiDoc['uri'], $apiDoc['method'], $apiDoc['header'] ?? [], $apiDoc['param'] ?? []);
        return $code;
    }

    /**
     * 获取调试数据
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function debug(Request &$request, Response &$response){
        $app = $request->getRoure()['extra'][0];
        $data = $request->validateRule([
            'protocol' => 'required',
            'version' => 'string',
            'uri' => 'required',
            'method' => 'required'
        ])->getData();
        $apiDoc = App::make(ApiDocService::class)->detail($app, $data['protocol'], $data['uri'], $data['version'] ?? "", $data['method'])['detail'][0];
        return $apiDoc;
    }

    public function success(Request &$request, Response &$response){
        $app = $request->getRoure()['extra'][0];
        $data = $request->validateRule([
            'protocol' => 'required',
            'version' => 'string',
            'uri' => 'required',
            'method' => 'required',
            'body' => 'required',
        ])->getData();
        $apiDoc = App::make(ApiDocService::class)->detail($app, $data['protocol'], $data['uri'], $data['version'] ?? "", $data['method'])['detail'][0];
        if(is_array($data['body'])){
            $data['body'] = json_encode($data['body'], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
        }
        App::make(ApiDocService::class)->success($apiDoc['id'], $data['body']);
    }

    public function failure(Request &$request, Response &$response){
        $app = $request->getRoure()['extra'][0];
        $data = $request->validateRule([
            'protocol' => 'required',
            'version' => 'string',
            'uri' => 'required',
            'method' => 'required',
            'body' => 'required',
        ])->getData();
        $apiDoc = App::make(ApiDocService::class)->detail($app, $data['protocol'], $data['uri'], $data['version'] ?? "", $data['method'])['detail'][0];
        if(is_array($data['body'])){
            $data['body']['data'] = null;
            $data['body'] = json_encode($data['body'], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
        }
        print_r($data);
        App::make(ApiDocService::class)->failure($apiDoc['id'], $data['body']);
    }
}