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
    protected $versionList = [];
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
            App::error()->setError('apiDoc菜单文件还没有生成', Error::NOT_FOUND);
        }
        $this->menuList = json_decode(file_get_contents($menuFile), true);

        $versionFile = App::runtimePath().DIRECTORY_SEPARATOR.'apidoc'.DIRECTORY_SEPARATOR.$app.'-version.json';
        if(!file_exists($versionFile)){
            App::error()->setError('apiDoc版本文件还没有生成', Error::NOT_FOUND);
        }

        $this->versionList = json_decode(file_get_contents($versionFile), true);
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

    protected function getApiMethods($uri){
        $methods = array_keys($this->menuList[$uri]['methods'] ?? []);
        sort($methods);
        $methods = array_values($methods);
        $method0 = $methods[0] ?? App::error()->setError('没有相关数据', Error::NOT_FOUND);
        if($method0 == 'DELETE'){
            unset($methods[0]);
            $methods[99999] = 'DELETE';
            $methods = array_values($methods);
        }

        $data = [];
        foreach($methods as $v){
            $data[] = $this->menuList[$uri]['methods'][$v];
        }
        return $data;
    }

    /**
     * 获取api组 同uri不同版本不同方法的api归一组
     * @param Request $request
     * @param Response $response
     * @return array
     * @throws \ReflectionException
     */
    public function apis(Request &$request, Response &$response){
        $uri = $request->param('uri');
        // 已知的请求方法 排序方式为GET POST PUT DELETE
        $methods = $this->getApiMethods($uri);

        $apiDocs = [];

        foreach($methods as $v){
            $uris = explode('/', $v['uri']);
            $version = $uris[0];
            $fullUri = '/'.$uris[1];

            $route = App::route()->getAll()[$this->app][$version][$fullUri][$v['method']] ?? App::error()->setError($request->param('uri').'路由未注册', Error::NOT_FOUND);

            $handler = $route['handler'];
            $class = new \ReflectionClass($handler[0]);
            $method = $class->getMethod($handler[1]);
            $apiDoc = (new Annotations($method->getDocComment()))->all();
            $apiDocs[] = [
                'uri' => '/'.$version.'/'.explode('.', $uri)[0].'.'.$apiDoc['api']['uri'],
                'title' => $apiDoc['api']['title'],
                'description' => $apiDoc['apiDescription'],
                'method' => $route['method'],
                'header' => array_values($apiDoc['apiHeader']),
                'param' => array_values($apiDoc['apiParam']),
                'success' => array_values($apiDoc['apiSuccess']),
                'error' => $apiDoc['apiError'],
                'response' => [], // 响应示例
                'version' => $version,
                'versions' => array_unique($this->versionList[$uri][$route['method']])
            ];
        }
        return $apiDocs;
    }

    /**
     * 获取单个api
     * @param Request $request
     * @param Response $response
     * @return array
     */
    public function api(Request &$request, Response &$response){
        $uri = $request->param('uri');
        $method = $request->param('method');
        $uris = explode('/', $uri);
        $version = $uris[0];
        $fullUri = '/'.$uris[1];
        $route = App::route()->getAll()[$this->app][$version][$fullUri][$method] ?? App::error()->setError($request->param('uri').'路由未注册', Error::NOT_FOUND);

        $handler = $route['handler'];
        $class = new \ReflectionClass($handler[0]);
        $method = $class->getMethod($handler[1]);
        $apiDoc = (new Annotations($method->getDocComment()))->all();
        return [
            'uri' => '/'.$version.'/'.explode('.', $uris[1])[0].'.'.$apiDoc['api']['uri'],
            'title' => $apiDoc['api']['title'],
            'description' => $apiDoc['apiDescription'],
            'method' => $route['method'],
            'header' => $apiDoc['apiHeader'],
            'param' => $apiDoc['apiParam'],
            'success' => $apiDoc['apiSuccess'],
            'error' => $apiDoc['apiError'],
            'response' => [], // 响应示例
        ];
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