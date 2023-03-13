<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / ApiDocController.php    [ 2023/3/7 15:10 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: Administrator <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\apidoc;

use zhanshop\App;
use zhanshop\Request;
use zhanshop\apidoc\ApiDocService;

class ApiDocController
{
    protected $appType = 'http';

    protected $apiPwd = 'zhangqiquan';

    protected ApiDocService $service;
    /**
     * 前置中间件
     * @var array
     */
    protected $beforeMiddleware = [];

    /**
     * 后置中间件
     * @var array
     */
    protected $afterMiddleware = [];
    /**
     * api文档入口
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function apidoc(Request &$request){
        if(($_SERVER['APP_ENV'] ?? 'dev') == 'production') App::error()->setError('访问的接口不存在', 404);
        $method = $request->method();
        if(!in_array($method, ['login', 'apis', 'detail', 'debug', 'cross', 'update'])) App::error()->setError('访问的接口不存在', 403);
        $this->service = new ApiDocService($this->appType);
        if($request->post('_auth') == false || $request->post('_auth') != $this->apiPwd) App::error()->setError("请先输入访问密码", 10001);
        return $this->$method($request);
    }

    /**
     * 获取api列表
     * @param Request $request
     * @return void
     */
    protected function apis(Request &$request){
        return [
            'menu' => $this->service->getApiMenu(),
            'apptype' => $this->appType,
        ];
    }

    /**
     * 获取api详情
     * @param Request $request
     * @return void
     */
    protected function detail(Request &$request){
        $input = &App::validate()->check($request->post(), [
            'uri' => 'Required',
            'version' => 'Required',
        ]);
        return $this->service->getDetail($input['version'], $input['uri']);
    }

    public function cross(){
        $include = include App::rootPath().DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'autoload'.DIRECTORY_SEPARATOR.'http.php';
        $cross = $include['servers']['cross'] ?? 'TOKEN';
        $arr = explode(',', $cross);
        $cross = [];
        foreach($arr as $v){
            $cross[] = trim($v, ' ');
        }
        return $cross;
    }

    protected function update(Request &$request){
        $post = $request->post();
        unset($post['_auth']);
        $this->service->update($post);
    }

    /**
     * api调试信息
     * @param Request $request
     * @return void
     */
    protected function debug(Request &$request){
        $input = &App::validate()->check($request->post(), [
            'uri' => 'Required',
            'version' => 'Required',
            'result' => 'Required',
            'request_method' => 'Required',
        ]);
//        if($input['result']){
//            $json = json_decode($input['result'], true);
//            if($json) $input['result'] = $json;
//        }

        return $this->service->debug($input['uri'], $input['version'], $input['result'], $input['request_method']);

    }

    public function result(mixed &$data = [], $msg = '成功', $code = 0){
        return [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ];
    }

    /**
     * 获取前置控制中间件
     */
    public function getBeforeMiddleware(){
        return $this->beforeMiddleware;
    }

    /**
     * 获取异步控制中间件
     * @return array
     */
    public function getAfterMiddleware(){
        return $this->afterMiddleware;
    }
}