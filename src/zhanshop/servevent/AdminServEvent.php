<?php

namespace zhanshop\servevent;

use zhanshop\App;
use zhanshop\ClientInfo;
use zhanshop\console\command\Server;
use zhanshop\Log;
use zhanshop\ServEvent;

class AdminServEvent extends ServEvent
{
    /**
     * http请求
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     * @param string $group
     * @return void
     */
    public function onRequest($request, $response, $protocol = Server::HTTP, $appName = 'admin') :void{
        parent::onRequest($request, $response, $protocol, $appName);
    }
}