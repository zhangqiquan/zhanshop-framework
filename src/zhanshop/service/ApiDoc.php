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

    /**
     * 调用
     * @param Request $request
     * @param Response $response
     * @return array
     * @throws \Exception
     */
    public function call(Request &$request, Response &$response){
        $this->init();
        $method = $request->method();
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
    public function detail($app, $protocol, $uri, $method = '', $version = ''){
        return [];
    }
}