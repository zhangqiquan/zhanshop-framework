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
use zhanshop\Request;
use zhanshop\Response;

class ApiDoc
{
    /**
     * 初始化
     * @param string $appName
     * @return void
     * @throws \Exception
     */
    public function init(){
        if(($_SERVER['APP_ENV'] ?? 'dev') == 'production') App::error()->setError('访问的接口不存在', Error::NOT_FOUND);
    }

    public function get(Request &$request, Response &$response){
        $app = $request->getRoure()['extra'][0];
        $data = file_get_contents(App::runtimePath().DIRECTORY_SEPARATOR.'apidoc'.DIRECTORY_SEPARATOR.$app.'-menu.json');
        $data = json_decode($data, true);
        return [
            'menu' => array_values($data),
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
        $this->init();
        $method = strtolower($request->method());
        $data = $this->$method($request, $response);
        return [
            'code' => 0,
            'msg' => 'ok',
            'data' => $data,
        ];
    }

    /**
     * 获取菜单列表
     * @return array
     */
    public function index(Request &$request, Response &$response){
        $app = $request->getRoure()['extra'][0];
        return [
            'menu' => App::make(ApiDocService::class)->menu($app),
            'title' => App::config()->get('app.app_name', 'ZhanShop'),
            'app' => $app,
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
        $app = $request->getRoure()['extra'][0];
        $data = $request->validateRule([
            'protocol' => 'required',
            'version' => 'string',
            'uri' => 'required'
        ])->getData();
        return App::make(ApiDocService::class)->detail($app, $data['protocol'], $data['uri'], $data['version'] ?? "");
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