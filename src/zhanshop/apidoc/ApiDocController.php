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
        if(!in_array($method, ['login', 'apis', 'detail', 'debug', 'success', 'cross', 'update', 'samplecode'])) App::error()->setError('api文档'.$method.'方法未定义', Error::NOT_FOUND);
        return $method;
    }
    /**
     * api文档入口
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function postApidoc(Request &$request, Response &$response){
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
     * 更新成功请求示例
     * @param Request $request
     * @return void
     */
    public function success(Request &$request){
        $app = $this->appName;
        $data = $request->param();
        $body = $data['body'];
        unset($data['body']);
        $data['app'] = $app;
        $data['success'] = is_array($body) ? json_encode($body, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE) : $body;
        $this->service->update($data);
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
        $data = $request->validateRule([
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
     * api调试信息.
     * @param Request $request
     * @return void
     */
    protected function debug(Request &$request){
        $input = $request->validateRule($request->post(), [
            'protocol' => 'Required',
            'uri' => 'Required',
            'version' => 'Required',
            'method' => 'Required',
        ])->getData();
        return $this->service->getDetail($input['protocol'], $input['version'], $input['uri'], $input['method'])['detail'][0];

    }

    public function samplecode(Request &$request){
        $method = $request->param('method');
        $language = $request->param('language');
        //print_r($this->service->getDetail($request->param('protocol'), $request->param('version'), $request->param('uri'), $method));
        $code = SampleCode::$language($this->service->getDetail($request->param('protocol'), $request->param('version'), $request->param('uri'), $method)['detail'][0], $request);
        return $code;
    }

    public function result(mixed &$data = [], $msg = '成功', $code = 0){
        return [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ];
    }
}

class SampleCode{

    public static function vue3($detail, &$request){
        $headerCode = '{'.PHP_EOL;

        foreach($detail['header'] ?? [] as $v){
            $headerCode .= '        "'.$v['name'].'": '.("\""."\",").' //'.$v['description'].PHP_EOL;
        }
        $headerCode .= '    }';

        $bodyCode = '{'.PHP_EOL;
        foreach($detail['param'] ?? [] as $v){
            $json = json_decode((string)($v['example'] ?? ""), true);
            if(is_array($json) == false && is_string($v['example'])){
                $v['example'] = '"'.$v['example'].'"';
            }
            if($v['example'] === null){
                $v['example'] = '""';
            }
            $bodyCode .= '        "'.$v['name'].'": '.$v['example'].', //'.$v['description'].PHP_EOL;
        }
        $bodyCode .= '    }';
        $uri = $request->param('uri');
        $method = $request->param('method', "GET");
        $type = $request->param('type');
        $version = $request->param('version');
        $code = "// vuejs示例代码 //
axios.request({
    // 请求的接口地址
    url: \"".($request->header('origin').'/'.$request->post('version').'/'.$request->post('uri'))."\",
    //请求方法
    method: \"".strtoupper($method)."\",
    //超时时间设置，单位毫秒
    timeout: 30000,
    //请求头参数
    headers: ".$headerCode.",
    // 与请求一起发送的 URL 参数
    params: {},
    // 请求主体被发送的数据
    data: ".$bodyCode.",
}).then(res => {
    // 请求成功处理
    alert(JSON.stringify(res));
}).catch(function (error) {
    // 请求失败处理
    alert(error.message);
});".PHP_EOL;
        return $code;
    }

    public static function jquery($detail, &$request){
        $headerCode = '{'.PHP_EOL;

        foreach($detail['header'] as $v){
            $headerCode .= '        "'.$v['name'].'": '. (is_int($v['default'] ?? '') ? $v['default'] : ("\"".($v['default'] ?? '')."\",")).' //'.$v['description'].PHP_EOL;
        }
        $headerCode .= '    }';

        $bodyCode = '{'.PHP_EOL;
        foreach($detail['param'] ?? [] as $v){
            $json = json_decode($v['example'], true);
            if(is_array($json) == false && is_string($v['example'])){
                $v['example'] = '"'.$v['example'].'"';
            }
            $bodyCode .= '        "'.$v['name'].'": '.$v['example'].', //'.$v['description'].PHP_EOL;
        }
        $bodyCode .= '    }';
        $uri = $request->param('uri');
        $method = $request->param('method');
        $type = $request->param('type');
        $version = $request->param('version');
        $code = "// jquery示例代码 //
var request = $.ajax({
    //请求的接口地址
    url: '".($request->header('origin').'/'.$request->post('version').'/'.$request->post('uri'))."',
    //请求方法
    method: '".strtoupper($method)."',
     //超时时间设置，单位毫秒
    timeout: 30000,
    //请求头参数
    headers: ".$headerCode.",
    //请求的数据
    data: ".$bodyCode.",
});
// 请求成功
request.done(function(data) {
    alert(JSON.stringify(data));
});
// 请求异常
request.fail(function(jqXHR) {
    alert(\"接口出错\\n\"+jqXHR.statusText);
});".PHP_EOL;
        return $code;
    }


    public static function java($detail, &$request){
        return '';
    }

    public static function go($detail, &$request){
        return '';
    }

    public static function python($detail, &$request){
        return '';
    }

    public function curl($detail, &$request){
        return '';
    }

    /**
     * 获取c语言的示例代码
     * @param $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($name, array $arguments)
    {
        return self::get($name, $arguments);
    }


}