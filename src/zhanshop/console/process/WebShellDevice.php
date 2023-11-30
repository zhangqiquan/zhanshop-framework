<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / WebShell.php    [ 2023/11/30 13:16 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\console\process;

use Swoole\Coroutine;
use Swoole\Coroutine\Http\Client;
use Swoole\WebSocket\Frame;
use zhanshop\App;
use zhanshop\console\command\Help;
use zhanshop\Helper;

class WebShellDevice
{
    protected array $config = [];

    protected function init(){
        $taskHost = explode(':', App::env()->get("WEBSHELL_HOST", "127.0.0.1:8201"));
        $this->config = [
            'ip' => $taskHost[0],
            'port' => (int)$taskHost[1],
            'options' => [
                'keep_alive' => true,
                'websocket_mask' => false,
                'websocket_compression' => true,
                'timeout' => 3.0, //总超时，包括连接、发送、接收所有超时
                'connect_timeout' => 1.0,//连接超时，会覆盖第一个总的 timeout
                'write_timeout' => 3.0,//发送超时，会覆盖第一个总的 timeout
                'read_timeout' => 5.0,//接收超时，会覆盖第一个总的 timeout
            ]
        ];
    }

    public function execute($server)
    {
        $this->init();
        Coroutine::create(function (){
            $this->listen();
        });
    }

    public function listen(){
        $client = new Client($this->config['ip'], $this->config['port']);
        $client->set($this->config['options']);
        $deviceId = str_replace(':', '', PHP_OS.'-'.($_SERVER['COMMAND_MODE'] ?? '').'-'.($_SERVER['USER'] ?? ''));
        $ok = $client->upgrade('/register?id='.$deviceId);
        if($ok == false){
            sleep((int)$this->config['options']['timeout']); // 重试
            $this->listen();
        }

        $pingFrame = new Frame;
        $pingFrame->opcode = SWOOLE_WEBSOCKET_OPCODE_PONG;

        while(true) {
            $recv = $client->recv();
            if($recv == false){
                // 发送心跳
                if($client->push($pingFrame) == false){
                    $client->close();
                    break;
                }
            }else if($recv->opcode != SWOOLE_WEBSOCKET_OPCODE_PONG){
                $data = json_decode($recv->data, true);
                $event = $data['event'];
                $resp = $this->$event($data['body'], $data['tofd']);
                $data['body'] = $resp;
                $client->push(json_encode($data, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE));
            }
        }
        $this->listen(); // 重新连接监听
    }

    /**
     * 执行命令
     * @param $command
     * @param $toFd
     * @return false|string
     */
    protected function command($command, $toFd){
        $logDir = App::runtimePath().'/webshell/';
        Helper::mkdirs($logDir);
        $logFile = App::runtimePath().'/webshell/'.$toFd.'.log';
        passthru($command." > ".$logFile, $code);
        if($code !== 0){
            return $command.' invalid command';
        }
        $logData = file_get_contents($logFile);
        @unlink($logFile);
        return $logData;
    }
}