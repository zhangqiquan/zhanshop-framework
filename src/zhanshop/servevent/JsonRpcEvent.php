<?php
// +----------------------------------------------------------------------
// | admin / JsonRpc.php    [ 2023/7/4 下午5:26 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\servevent;

use zhanshop\App;
use zhanshop\Error;
use zhanshop\Response;
use zhanshop\ServEvent;

class JsonRpcEvent extends ServEvent
{
    public function onReceive(\Swoole\Server $server, int $fd, int $reactorId, string $data) :void{

        try {
            $data = $this->getData($data);
            $path = $data['path'];
            $uris = explode('/', $path);
            $class = $this->urlClass($uris[1] ?? '');
            $server->send($fd, json_encode([
                'code' => 0,
                'msg' => 'OK',
                'data' => $this->callJsonRpc($class, $uris[2] ?? '', $data['body'] ?? [])
            ]), JSON_UNESCAPED_UNICODE);
        }catch (\Throwable $e){
            $server->send($fd, json_encode([
                'code' => $e->getCode() ? $e->getCode() : 500,
                'msg' => $e->getMessage(),
                'data' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTrace(),
                ]
            ],JSON_UNESCAPED_UNICODE));
        }
    }

    private function getData(string &$data){
        $data = json_decode($data, true);
        if($data == false) App::error()->setError($data.PHP_EOL.'不是一个有效的json', 400);
        if(isset($data['path']) == false) App::error()->setError('没有指定访问地址参数path', 400);
        return $data;
    }

    /**
     * 根据path获取service类
     * @param string $uri
     * @return string
     * @throws \Exception
     */
    private function urlClass(string $uri){
        if($uri == false) App::error()->setError('请求参数错误', Error::FORBIDDEN);
        $uri = explode('.', $uri);
        $class = '';
        foreach($uri as $v){
            $class .= $v.'\\';
        }
        $class = rtrim($class, '\\');
        return $class;
    }

    /**
     * 调用jsonRpc服务类
     * @param string $class
     * @param string $method
     * @param mixed $data
     * @return mixed
     * @throws \Exception
     */
    private function callJsonRpc(string $class, string $method, mixed $data){
        if($method == false) App::error()->setError('请求方法错误', Error::FORBIDDEN);
        $method = lcfirst($method);
        $arr = App::route()->getJsonRpc($class, $method);
        $request = new $arr[0]($data);
        $response = new $arr[1];
        App::make($class)->$method($request, $response);
        return $response->toArray();
    }
}