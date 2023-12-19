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
    public function onReceive($server, $fd, $reactorId, $data) :void{

        try {
            $data = $this->getData($data);
            $uris = explode('/', $data['path']);
            if(isset($uris[1]) && isset($uris[2])){
                $service = App::route()->getJsonRpc('/'.$uris[1], $uris[2]);

                $method = $uris[2];
                $req = new $service['param'][0]($data['body'] ?? []);
                $resp = new $service['param'][1];
                App::make($service['service'])->$method($req, $resp);
                $server->send($fd, json_encode([
                    'code' => 0,
                    'msg' => 'OK',
                    'data' => $resp->toArray()
                ]), JSON_UNESCAPED_UNICODE);
            }
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
}