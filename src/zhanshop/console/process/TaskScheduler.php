<?php

namespace zhanshop\console\process;

use Swoole\Coroutine;
use Swoole\Coroutine\Http\Client;
use Swoole\WebSocket\Frame;
use zhanshop\App;
use zhanshop\Log;

class TaskScheduler
{
    protected array $config = [];

    protected function init(){
        $taskHost = explode(':', App::env()->get("TASK_HOST", "127.0.0.1:7201"));
        $maxConnections = (int)App::env()->get("TASK_SCHEDULER_CONN", "20");
        $this->config = [
            'ip' => $taskHost[0],
            'port' => (int)$taskHost[1],
            'max_connections' => $maxConnections,
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

    /**
     * 启动分布式任务调度进程
     * @param $server
     * @return void
     */
    public function execute($server)
    {
        $this->init();
        for ($c = $this->config['max_connections']; $c--;) {
            Coroutine::create(function () use ($c){
                $this->listen();
            });
        }
    }

    /**
     * 监听任务连接
     * @return void
     */
    protected function listen(){
        $client = new Client($this->config['ip'], $this->config['port']);
        $client->set($this->config['options']);
        $ok = $client->upgrade('/');
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
                if($data){
                    try {
                        $resp = $this->dispatch($data['handler'], $data['param']);
                        $ok = $client->push(json_encode([
                            'code' => 0,
                            'msg' => 'ok',
                            'data' => $resp,
                            'notifyfd' => $data['notifyfd'], // 需要通知的fd
                        ], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE));
                    }catch (\Throwable $e){
                        $client->push(json_encode([
                            'code' => 417,
                            'msg' => $e->getMessage(),
                            'data' => ['file' => $e->getFile(), 'line' => $e->getLine()],
                            'notifyfd' => $data['notifyfd'], // 需要通知的fd
                        ]));
                    }
                }
            }
        }
        $this->listen(); // 重新连接监听
    }

    /**
     * 调度任务
     * @param string $task
     * @param array $param
     * @return mixed
     */
    public function dispatch(string $task, array $param = []){
        list($class, $action) = explode('@', $task);
        return App::make($class)->$action(...$param);
    }
}