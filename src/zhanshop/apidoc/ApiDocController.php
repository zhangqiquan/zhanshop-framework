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
use zhanshop\Error;
use zhanshop\Request;
use zhanshop\apidoc\ApiDocService;
use zhanshop\Response;

class ApiDocController
{
    protected $apiPwd = 'zhangqiquan';

    protected $appName = '';
    /**
     * @var ApiDocService
     */
    protected $service = null;

    protected function auth($auth){
        if($auth != $this->apiPwd) App::error()->setError("请先输入访问密码", 1001);
    }

    protected function method(string $method){
        if($method == 'GET'){
            $method = 'apis';
        }
        if(!in_array($method, ['login', 'apis', 'detail', 'debug', 'cross', 'update'])) App::error()->setError('api文档'.$method.'方法未定义', Error::NOT_FOUND);
        return $method;
    }
    /**
     * api文档入口
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function apidoc(Request &$request, Response &$response){
        $roure = $request->getRoure();
        $appName = $roure['extra'][0] ?? null;
        $this->appName = $appName;
        if($appName == false){
            return null;
        }

        if(($_SERVER['APP_ENV'] ?? 'dev') == 'production') App::error()->setError('访问的接口不存在', Error::NOT_FOUND);

        $method = $this->method($request->method());

        $this->service = new ApiDocService($appName);

        $data = $this->$method($request);
        return $this->result($data);
    }

    /**
     * 获取api列表
     * @param Request $request
     * @return void
     */
    protected function apis(Request &$request){
        return [
            'menu' => $this->service->getApiMenu(),
            'title' => App::config()->get('app.app_name', 'ZhanShop'),
            'app' => $this->appName,
        ];
    }

    /**
     * 获取api详情
     * @param Request $request
     * @return void
     */
    protected function detail(Request &$request){
        $data = $request->validate([
            'protocol' => 'Required',
            'uri' => 'Required',
            'version' => 'Required',
        ])->getData();
        return $this->service->getDetail($data['protocol'], $data['version'], $data['uri']);
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