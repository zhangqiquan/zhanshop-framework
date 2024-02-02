<?php

namespace zhanshop\console\command\server;

class Accepted
{
    protected $tcpConnections = [];

    /**
     * 设置被连接的新连接
     * @param int $fd
     * @return void
     */
    public function newConnection(int $fd)
    {
        $this->tcpConnections[$fd] = new TcpConnection();
    }

    /**
     * 获取连接对象
     * @param int $fd
     * @return TcpConnection
     */
    public function getConnection(int $fd)
    {
        return $this->tcpConnections[$fd];
    }

    /**
     * 关闭销毁连接
     * @param int $fd
     * @return void
     */
    public function closeConnection(int $fd)
    {
        unset($this->tcpConnections[$fd]);
    }
}