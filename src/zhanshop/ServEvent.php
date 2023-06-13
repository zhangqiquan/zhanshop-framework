<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / Event.php    [ 2023/4/12 16:58 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop;

use Swoole\Timer;
use zhanshop\cache\CacheManager;
use zhanshop\console\command\Server;
use zhanshop\database\DbManager;

class ServEvent
{
    /**
     * 当前工作进程的server对象
     * @var \Swoole\Server
     */
    public mixed $server;

    /**
     * 当前所有的服务名称
     * @param array $servNames
     */
    public $servNames = [];

    /**
     * 路由组
     * @var string
     */

    public const ON_START = 'start';
    public const ON_WORKER_START = 'workerStart';
    public const ON_WORKER_STOP = 'workerStop';
    public const ON_WORKER_EXIT = 'workerExit';
    public const ON_WORKER_ERROR = 'workerError';
    public const ON_PIPE_MESSAGE = 'pipeMessage';
    public const ON_REQUEST = 'request';
    public const ON_RECEIVE = 'receive';
    public const ON_CONNECT = 'connect';
    public const ON_HAND_SHAKE = 'handshake';
    public const ON_OPEN = 'open';
    public const ON_MESSAGE = 'message';
    public const ON_CLOSE = 'close';
    public const ON_TASK = 'task';
    public const ON_FINISH = 'finish';
    public const ON_SHUTDOWN = 'shutdown';
    public const ON_PACKET = 'packet';
    public const ON_MANAGER_START = 'managerStart';
    public const ON_MANAGER_STOP = 'managerStop';
    public const ON_BEFORE_RELOAD = 'onBeforeReload';
    public const ON_BEFORE_SHUTDOWN = 'onBeforeShutdown';
    public const ON_AFTER_RELOAD = 'onAfterReload';

    /**
     * 构造函数
     * @param mixed $server
     * @param array $servNames
     */
    public function __construct(mixed &$server, array $servNames)
    {
        $this->server = $server;
        $this->servNames = $servNames;
    }

    /**
     * 设置server对象
     * @param \Swoole\Server $server
     * @return void
     */
    public function setServer(mixed $server) :void{
        $this->server = $server;
    }

    /**
     * 获取server对象
     * @return \Swoole\Server
     */
    public function server() :mixed{
        return $this->server ?? null;
    }
    /**
     * 主进程启动回调
     * @param \Swoole\Server $server
     * @return void
     */
    public function onStart(\Swoole\Server $server) :void{
        $msg = "启动进程数".($this->config['settings']['worker_num'] ?? swoole_cpu_num()).', 线程数'.($this->config['settings']['reactor_num'] ?? swoole_cpu_num())*($this->config['settings']['worker_num'] ?? swoole_cpu_num()).', 进程'.getmypid().', swoole'.swoole_version().', 环境'.($_SERVER['APP_ENV'] ?? 'dev');
        Log::errorLog(SWOOLE_LOG_NOTICE, $msg);
        App::make(Robot::class)->send($msg);
    }

    /**
     * Worker进程启动回调
     * @param \Swoole\Server $server
     * @param int $workerId
     * @return void
     */
    public function onWorkerStart(\Swoole\Server $server, int $workerId) :void{
        $msg = $workerId."号工作进程启动, 进程".getmypid();
        Log::errorLog(SWOOLE_LOG_DEBUG, $msg);
        App::make(Robot::class)->send($msg);
        
        App::cleanAll(); // 清空销毁APP容器的所有对象

        App::webhandle($this); // webServer处理
    }

    /**
     * Worker(退出后)进程终止时回调
     * @param \Swoole\Server $server
     * @param int $workerId
     * @return void
     */
    public function onWorkerStop(\Swoole\Server $server, int $workerId) :void{
    }

    /**
     * Worker进程退出前回调
     * @param \Swoole\Server $server
     * @param int $workerId
     * @return void
     */
    public function onWorkerExit(\Swoole\Server $server, int $workerId) :void{
        Timer::clearAll();
    }

    /**
     * Worker进程发生错误时回调
     * @param \Swoole\Server $server
     * @param int $worker_id
     * @param int $worker_pid
     * @param int $exit_code
     * @param int $signal
     * @return void
     */
    public function onWorkerError(\Swoole\Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal) :void{
        // 使用 gdb 来跟踪 swoole 前，需要在编译时添加 --enable-debug 参数以保留更多信息
        // ulimit -c unlimited 开启核心转储文件
        // gdb php core || gdb php /tmp/core.1234 键入命令进入 gdb 调试程序
        // bt 紧接着输入 bt 并回车，就可以看到出现问题的调用栈
        // f 1 || f 0 可以通过键入 f 数字 来查看指定的调用栈帧
        // 将以上信息都贴在 issue 中
        $msg = '工作进程'.$worker_id.',所属进程'.$worker_pid.'退出码'.$exit_code.'退出信号';
        Log::errorLog(SWOOLE_LOG_ERROR, $msg);
        App::make(Robot::class)->send($msg);
    }

    /**
     * Worker进程收到由 $server->sendMessage() 发送的 unixSocket 消息时会触发 onPipeMessage 事件。worker/task 进程都可能会触发 onPipeMessage 事件
     * @param \Swoole\Server $server
     * @param int $src_worker_id
     * @param mixed $message
     * @return void
     */
    public function onPipeMessage(\Swoole\Server $server, int $src_worker_id, mixed $message) :void{

    }

    /**
     * http请求
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     * @param string $group
     * @return void
     */
    public function onRequest(mixed $request, mixed $response, int $protocol = Server::HTTP, string $appName = 'index') :void{
        $response->header('Server', 'zhanshop');
        $response->header('Access-Control-Allow-Origin', '*');
        // $response->header('Access-Control-Allow-Headers', ['servers']['cross']);
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, DELETE');
        $response->header('Access-Control-Max-Age', '3600');
        if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico' || $request->server['request_method'] == 'OPTIONS') {
            $response->end();
            return;
        }else if($request->server['request_uri'] == '/_status' && ($request->get['app_key'] ?? '') == App::config()->get('app.app_key')){
            $response->end(json_encode($this->server->stats()));
            return;
        }

        $servRequest = new Request($protocol, $request);
        $servResponse = new Response($response, $request->fd);

        App::webhandle()->dispatch($appName, $servRequest, $servResponse);
        $servResponse->sendHttp();
    }

    /**
     * 收到tcp数据[可能会出现粘包]
     * @param \Swoole\Server $server
     * @param int $fd
     * @param int $reactorId
     * @param string $data
     * @return void
     */
    public function onReceive(\Swoole\Server $server, int $fd, int $reactorId, string $data) :void{
        if($data == 'servStatus'){
            $server->send($fd, json_encode($server->stats()));
            return;
        }
    }

    /**
     * 有新的连接进入时，在 worker 进程中回调
     * @param \Swoole\Server $server
     * @param int $fd
     * @param int $reactorId
     * @return void
     */
    public function onConnect(\Swoole\Server $server, int $fd, int $reactorId) :void{

    }

//    /**
//     * 自定义握手处理
//     * @param Swoole\Http\Request $request
//     * @param Swoole\Http\Response $response
//     * @return void
//     */
//    public static function onHandShake($request, $response){
//
//    }

    /**
     * WebSocket 客户端与服务器建立连接并完成握手后
     * @param \Swoole\Server $server
     * @param \Swoole\Http\Request $request
     * @return void
     */
    public function onOpen(Swoole\WebSocket\Server $server, mixed $request) :void{
    }

    /**
     * WebSocket收到来自客户端的数据帧时会回调此函数
     * @param \Swoole\Server $server
     * @param $frame
     * @return void
     */
    public function onMessage(Swoole\WebSocket\Server $server, Swoole\WebSocket\Frame $frame) :void{
    }

    /**
     * TCP 客户端连接关闭后，在 Worker 进程中回调此函数
     * @param \Swoole\Server $server
     * @param int $fd
     * @param int $reactorId
     * @return void
     */
    public function onClose(\Swoole\Server $server, int $fd, int $reactorId) :void{

    }

    /**
     * 接收到新的task任务
     * @param \Swoole\Server $server
     * @param $task
     * @return void
     */
    public function onTask(\Swoole\Server $server, $task) :void{
        try{
            if($task->data == false || !is_array($task->data)){
                Log::errorLog(SWOOLE_LOG_ERROR,'投递的task必须是一个数组');
            }
            call_user_func_array($task->data['callback'], $task->data['value']);
        }catch (\Throwable $e){
            Log::errorLog(SWOOLE_LOG_ERROR,'task出错 '.$e->getMessage().PHP_EOL.'#@ '.$e->getFile().':'.$e->getLine().PHP_EOL.$e->getTraceAsString().PHP_EOL);
        }
    }

    /**
     * 处理异步任务的结果(此回调函数在worker进程中执行)
     * @param \Swoole\Server $server
     * @param int $task_id
     * @param mixed $data
     * @return void
     */
    public function onFinish(\Swoole\Server $server, int $task_id, mixed $data) :void{

    }

    /**
     * 触发了正常停止
     * @param \Swoole\Server $server
     * @return void
     */
    public function onShutdown(\Swoole\Server $server) :void{
        $pidFile = $server->setting['pid_file'] ?? '';
        if(file_exists($pidFile)) @unlink($pidFile);
        $msg = 'server触发了正常停止';
        Log::errorLog(SWOOLE_LOG_NOTICE, $msg);
        App::make(Robot::class)->send($msg);
    }

    /**
     * 接收到 UDP 数据包时回调此函数
     * @param \Swoole\Server $server
     * @param string $data
     * @param array $clientInfo
     * @return void
     */
    public function onPacket(\Swoole\Server $server, string $data, array $clientInfo) :void{

    }

    /**
     * 当管理进程启动时触发此事件
     * @param \Swoole\Server $server
     * @return void
     */
    public function onManagerStart(\Swoole\Server $server) :void{
        //echo PHP_EOL.'['.date('Y-m-d H:i:s').'] ###[info]###'." 管理进程启动, 进程".getmypid();
    }

    /**
     * 当管理进程结束时触发
     * @param \Swoole\Server $server
     * @return void
     */
    public function onManagerStop(\Swoole\Server $server) :void{
    }

    /**
     * Worker 进程 Reload 之前触发此事件，在 Manager 进程中回调
     * @param \Swoole\Server $server
     * @return void
     */
    public function onBeforeReload(\Swoole\Server $server) :void{
    }

    /**
     * Worker 进程 Reload 之后触发此事件，在 Manager 进程中回调
     * @param \Swoole\Server $server
     * @return void
     */
    public function onAfterReload(\Swoole\Server $server) :void{
    }

    /**
     * Server 正常结束前发生
     * @param \Swoole\Server $server
     * @return void
     */
    public function onBeforeShutdown(\Swoole\Server $server) :void{
    }
}