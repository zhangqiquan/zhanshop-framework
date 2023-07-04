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

use zhanshop\ServEvent;

class JsonRpcEvent extends ServEvent
{
    public function onReceive(\Swoole\Server $server, int $fd, int $reactorId, string $data) :void{
        if($data == 'servStatus'){
            $server->send($fd, json_encode($server->stats()));
            return;
        }

        $request = json_decode($data);
        var_dump($request);
    }
}

// 独立的请求类
class JsonRpcRequest{

}