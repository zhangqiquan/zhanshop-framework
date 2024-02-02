<?php
namespace zhanshop\console\command\server;

class TcpConnection
{
    protected $buff = "";
    public function setBuff($data)
    {
        $this->buff .= $data;
    }

    public function &getBuff()
    {
        return $this->buff;
    }
}