<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / TcpClient.php    [ 2023/4/24 14:12 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\client;

use Swoole\Coroutine\Client;
use zhanshop\App;

class TcpClient
{
    /**
     * @var Client
     */
    protected mixed $connect = null;

    /**
     * 连接
     * @param string $host
     * @param int $port
     * @param float $timeout
     * @return void
     * @throws \Exception
     */
    public function connect(string $host, int $port, float $timeout = 0.5){
        $client = new Client(SWOOLE_SOCK_TCP);
        if (!$client->connect($host, $port, $timeout))
        {
            App::error()->setError('tcp://'.$host.':'.$port.' 连接失败:'.$client->errCode);
        }
        $this->connect = $client;
    }

    /**
     * 发送消息
     * @param string $msg
     * @return void
     */
    public function send(string $msg){
        $this->connect->send($msg."\n");
    }

    /**
     * 读取消息
     * @return mixed
     */
    public function recv(){
        return $this->connect->recv();
    }

    /**
     * 销毁连接
     */
    public function __destruct()
    {
        $this->connect->close();
    }
}