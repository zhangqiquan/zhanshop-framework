<?php
// +----------------------------------------------------------------------
// | zhanshop-device / Socks5.php    [ 2023/08/26 下午1:42 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\servevent;

use zhanshop\App;
use zhanshop\bridg\proxy\Proxy;
use zhanshop\console\ServerStatus;
use zhanshop\Log;
use zhanshop\ShareData;


class BridgeSocks5Event
{

    protected $proxyMap = [];

    /**
     * @param \Swoole\Server $server
     * @param int $fd
     * @return void
     */
    protected function getProxyFd(mixed $server, int $fd)
    {
        if(isset($this->proxyMap[$fd])) return $this->proxyMap[$fd];

        $clientInfo = $server->getClientInfo($fd);
        if(isset($clientInfo['remote_ip']) == false){
            $server->close($fd);
            return false;
        }

        $bridgeProxyFile = App::runtimePath().'/bridge:proxy:map.json';
        $bridgeProxyMap = [];
        if(file_exists($bridgeProxyFile)){
            $jsonStr = file_get_contents($bridgeProxyFile);
            $bridgeProxyMap = json_decode($jsonStr, true);
        }

        if(isset($bridgeProxyMap[$clientInfo['remote_ip']]) == false){
            $server->close($fd);
            return false;
        }

        $bridgeName = $bridgeProxyMap[$clientInfo['remote_ip']];

        foreach (ShareData::getInstance() as $row){
            if($row['name'] == $bridgeName){
                $this->proxyMap[$fd] = $row['fd'];
                return $row['fd'];
            }
        }
        $server->close($fd);
        return false;
    }

    /**
     * @param \Swoole\Server $server
     * @param int $fd
     * @param int $reactorId
     * @return void
     */
    public function onConnect($server, $fd, $reactorId) :void{
    }

    /**
     * @param \Swoole\Server $server
     * @param int $fd
     * @param int $reactorId
     * @param string $data
     * @return void
     */
    public function onReceive($server, $fd, $reactorId, $data) :void{
        $toFd = $this->getProxyFd($server, $fd);
        if($toFd){
            $server->send($toFd, json_encode([
                    'uri' => '/v1/proxy.socks5Message/'.$fd,
                    'header' => [],
                    'body' => base64_encode($data)
                ])."\r\n");
        }
    }


    /**
     * 关闭消息
     * @param \Swoole\Server $server
     * @param int $fd
     * @return void
     */
    public function onClose($server, $fd) :void{
        $toFd = $this->getProxyFd($server, $fd);
        unset($this->proxyMap[$fd]);
        if($toFd){
            $server->send($toFd, json_encode([
                    'uri' => '/v1/proxy.socks5Close/'.$fd,
                    'header' => [],
                    'body' => ""
                ])."\r\n");
        }
    }
}