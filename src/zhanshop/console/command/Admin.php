<?php
// +----------------------------------------------------------------------
// | zhanshop-swoole / Http.php    [ 2021/12/30 4:18 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
namespace zhanshop\console\command;

use app\admin\Controller;
use zhanshop\App;
use zhanshop\cache\CacheManager;
use zhanshop\console\Command;
use zhanshop\console\command\admin\Menu;
use zhanshop\console\crontab\WatchCronTab;
use zhanshop\console\Input;
use zhanshop\console\Output;
use zhanshop\console\task\WatchTask;
use zhanshop\database\DbManager;
use zhanshop\Request;
use function Swoole\Coroutine\run;
use function Swoole\Coroutine\go;
use Swoole\Coroutine;
use Swoole\Coroutine\System;
use Swoole\Process;
use Swoole\Runtime;
set_time_limit(0);
class Admin extends Command
{
    protected $config = [
        'servers' => [
            'name'      => 'zhanshop',
            'mode'      => SWOOLE_PROCESS,
            'host' => '0.0.0.0',
            'port' => 9502,
            'sock_type' => SWOOLE_SOCK_TCP,
            'cross' => 'TOKEN',
        ],
        // 关于静态化访问 正式环境中不提供该功能
        'settings' => [
            'daemonize' => false,
            'enable_coroutine' => true,
            'send_yield' => true,
            'send_timeout' => 3, // 1.5秒
            'log_level' => SWOOLE_LOG_INFO, // 仅记录错误日志以上的日志
            'log_rotation' => SWOOLE_LOG_ROTATION_MONTHLY, // 每月日志
            'enable_deadlock_check' => false,
            'task_worker_num' => 1,
            'task_enable_coroutine' => true,
            'max_request' => 200000,
            'max_wait_time' => 2,
            'enable_static_handler' => true,
            'document_root' => '',
            'http_autoindex' => true,
            'http_index_files' => ['index.html']
        ],
        'task' => [
            'watchTask' => WatchTask::class,
        ],
        // 定时任务是在子进程启动之后就开始执行【定时任务不受reload影响，需要restart后生效】
        'crontab' => [
            'watchCrontab' => WatchCronTab::class,
        ],
    ];
    protected Input $input;
    protected Output $output;
    public function configure(){
        $this->setTitle('启动api服务')->setDescription('使用该命令可以创建一个http后台服务器');
    }

    public function execute(Input $input, Output $output){
        $this->config['settings']['pid_file'] = App::runtimePath().DIRECTORY_SEPARATOR.'admin.pid';
        $this->config['settings']['log_file'] = App::runtimePath().DIRECTORY_SEPARATOR.'server'.DIRECTORY_SEPARATOR.'admin.log';
        $config = include App::rootPath().DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'autoload'.DIRECTORY_SEPARATOR.'admin.php';
        $this->config['servers'] = array_merge($this->config['servers'], $config['servers']);
        $startType = $config['start'] ?? 'http';
        $this->config['settings'] = array_merge($this->config['settings'], $config['starts'][$startType] ?? []);

        $this->config['task'] = array_merge($this->config['task'], $config['task']);
        $this->config['crontab'] = array_merge($this->config['crontab'], $config['crontab']);

        $this->input = $input;
        $this->output = $output;

        $argv = $input->getArgv();
        if($argv == false) return $this->usage();

        $method = $argv[0];
        $daemonize = $argv[1] ?? 'true';
        if($this->config['settings']['document_root'] == '') $this->config['settings']['document_root'] = App::rootPath().DIRECTORY_SEPARATOR.'public';

        return $this->$method($daemonize == 'false' ? false : true);
    }

    public function __call($name,$arguments) {
        $msg = PHP_EOL.'暂不支持'.$name.'指令';
        $this->output->output($msg, 'error');

    }

    /**
     * 启动HTTP服务器
     * @param $daemon
     * @return void
     */
    public function start($daemon = true){
        // 实例化一个httpServer
        if (!extension_loaded('swoole')) return $this->output->output(PHP_EOL.'请先安装swoole扩展', 'error');

        if($this->isRuning()){
            return $this->output->output("程序已在运行...", 'error');
        }
        
        try {
            $server = new \Swoole\Http\Server($this->config['servers']['host'], $this->config['servers']['port'], $this->config['servers']['mode'], $this->config['servers']['sock_type']);
            if($daemon) $this->config['settings']['daemonize'] = $daemon;
            $server->set($this->config['settings']);
            \Co::set(['hook_flags'=> SWOOLE_HOOK_ALL]);
        }catch (\Throwable $e){
            return $this->output->output(PHP_EOL.$e->getMessage(), 'error');
        }

        $this->onEvent($server);
        echo PHP_EOL.'【'.date('Y-m-d H:i:s').'】'.$this->config['servers']['host'].':'.$this->config['servers']['port'].' - swoole版本: '.swoole_version().' 环境：'.($_SERVER['APP_ENV'] ?? 'dev').PHP_EOL;
        echo PHP_EOL.'启动进程数:'. ($this->config['settings']['worker_num'] ?? swoole_cpu_num()).',启动线程:'.($this->config['settings']['reactor_num'] ?? swoole_cpu_num())*($this->config['settings']['worker_num'] ?? swoole_cpu_num()).',进程ID:'. getmypid().PHP_EOL.PHP_EOL;

        $server->start();

    }

    /**
     * 是否运行
     * @return false
     */
    protected function isRuning(){
        try {
            $pid = intval(file_get_contents($this->config['settings']['pid_file']));
            if(\Swoole\Process::kill($pid, 0)){
                return $pid;
            }
        }catch (\Throwable $e){

        }
        return false;
    }

    /**
     * 终止进程运行
     * @param $daemon
     * @return mixed
     */
    public function stop($daemon = false){
        $pid = $this->isRuning();
        if($pid){
            try {
                \Swoole\Process::kill($pid, SIGTERM);
                $startTime = microtime(true);
                while ($this->isRuning()){
                    usleep(100);
                    if((microtime(true) - $startTime) > 10){
                        $this->output->output('终止程序失败', 'error');
                        exit();
                    }
                }
                if(file_exists($this->config['settings']['pid_file'])) @unlink($this->config['settings']['pid_file']);
            }catch (\Throwable $e){
                $this->output->output($e->getMessage(), 'error');
                exit();
            }
        }
    }

    /**
     * 重启
     * @param $daemon
     * @return void
     */
    public function restart($daemon = false){
        $this->stop();
        $this->start($daemon);
    }

    /**
     * 重载
     * @param $daemon
     * @return void
     */
    public function reload($daemon = false){
        $pid = $this->isRuning();
        if($pid){
            try {
                \Swoole\Process::kill($pid, SIGUSR1);
                $this->output->output("重载完成", 'success');
            }catch (\Throwable $e){
                $this->output->output($e->getMessage(), 'error');
                exit();
            }
        }else{
            $this->output->output("程序尚未启动", 'error');
        }
    }

    /**
     * 输出状态信息
     * @param $daemon
     * @return void
     */
    public function status($daemon = false){
        \Swoole\Coroutine\run(function() {
            if(!file_exists($this->config['settings']['pid_file'])){
                return $this->output->output(PHP_EOL.$this->config['listen'].'未运行或pid不存在', 'error');
            }
            $pid = file_get_contents($this->config['settings']['pid_file']);
            $arr = \Swoole\Coroutine\System::exec('lsof -i:'.$this->config['servers']['port']);
            echo PHP_EOL.'swoole版本: '.swoole_version().'   php版本: '.PHP_VERSION.PHP_EOL;
            $pRunTimes = \Swoole\Coroutine\System::exec('ps -p '.$pid.' -o etimes,etime');
            $pRunTimes = explode('       ', $pRunTimes['output']);
            if(isset($pRunTimes[1]) == false) return $this->output->output(PHP_EOL.$this->config['servers']['port'].'未运行', 'error');
            $pRunTime = intval(array_values(array_filter(explode('   ', explode("\n", $pRunTimes[0])[1])))[0]);
            echo PHP_EOL.'运行开始: '.date('Y-m-d H:i:s', time() - $pRunTime).'   运行时间: '.$pRunTime.'秒'.PHP_EOL.PHP_EOL;

            $connections = \Swoole\Coroutine\System::exec('netstat -nat|grep -i '.$this->config['servers']['port'].' |wc -l');
            $timeWaits = \Swoole\Coroutine\System::exec('netstat -nat|grep -i '.$this->config['servers']['port'].' | grep "TIME_WAIT" |wc -l');
            echo '连接数: ' .rtrim($connections['output'], "\n").'   TIME_WAIT: '.$timeWaits['output'];

            //ps -p 10167 -o etimes,etime
            //查看指定进程号的详细进程信息
            //$processInfos = \Swoole\Coroutine\System::exec('cat /proc/'.$pid.'/status');
            //print_r($processInfos);
            //echo PHP_EOL.'开始运行:'.swoole_version().'   已经运行:'.PHP_VERSION;
            echo PHP_EOL."==================================================================".PHP_EOL.PHP_EOL;
            echo $arr['output'];
        });
    }

    public function runWithRequest($request){

        $routeInfo = App::route()->callback($request->server['request_uri'] ?? App::error()->setError("uri参数获取失败", 500), $request->server['request_method']); // 检查路由
        $request->service = $routeInfo['service'];
        $method     = $routeInfo['action'];
        $this->controller = App::service()->get($routeInfo['controller']);

        App::middleware()->runBefore($this->request, $this->controller->getBeforeMiddleware()); // 运行前置中间件
        $routes = App::route()->check($request, 'http');
        $controller = App::service()->get($routes['controller']);
        $action = $routes['action'];
        return $controller->$action($request);
    }
    /**
     *
     * @param $server
     * @return void
     */
    protected function onEvent($server){
        $appConf = App::config()->get("app");
        $process = new \Swoole\Process(function ($process) use ($server) {
            foreach($this->config['crontab'] as $v){
                (new $v())->execute($server);
            }
        }, false, 2, true);
        $server->addProcess($process);

        // swoole_error_log() 使用这个函数输出日志
        $server->on('Request', function ($request, $response) use($server, $appConf) {
            $response->header('Server', 'zhanshop');
            $response->header('Access-Control-Allow-Origin', '*');
            $response->header('Access-Control-Allow-Headers', $this->config['servers']['cross']);
            $response->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, DELETE');
            $response->header('Access-Control-Max-Age', '3600');
            if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico' || $request->server['request_method'] == 'OPTIONS') {
                $response->end();
                return;
            }
            $httpCode = 200;
            $requestTime = $request->server["request_time_float"];
            $requestTraceId = md5($request->server['remote_addr'].$requestTime);
            $req = new Request($request);
            try {

                $routeInfo = App::route()->swooleCallback($request->server['request_uri'] ?? App::error()->setError("uri参数获取失败", 500), $request->server['request_method'], 'admin'); // 检查路由
                //$request->service = $routeInfo['service']; // 动态属性被弃用
                $req->setService($routeInfo['service']);
                $method     = $routeInfo['action'];
                $req->setAction($method);
                $controller = App::service()->get($routeInfo['controller']);
                App::middleware()->runBefore($req, $controller->getBeforeMiddleware()); // 运行前置中间件
                $data = $controller->$method($req);
                if(is_array($data) || is_object($data)){
                    $response->header('Content-Type', 'application/json; charset=utf-8');
                    $data = $controller->result($data);
                    $data['time'] = $requestTime;
                    $data['trace_id'] = $requestTraceId;
                    App::middleware()->runAfter($req, $data, $controller->getAfterMiddleware());
                    $response->status($httpCode);
                    $response->end(json_encode($data)); // 如果是数组的情况下
                }else{
                    $ret = $controller->result($data);
                    $ret['time'] = $requestTime;
                    $ret['trace_id'] = $requestTraceId;
                    App::middleware()->runAfter($req, $ret, $controller->getAfterMiddleware());
                    $response->status($httpCode);
                    //$this->respHeader($request, $response);
                    $response->end($data);
                }

            } catch (\Throwable $e) {
                // 触发用户错误
                $httpCode = $e->getCode() < 200 ? 500 : ($e->getCode() > 505 ? 417 : $e->getCode());
                $message = $e->getMessage();
                if($httpCode == 500 && $appConf['show_error_msg'] == false){
                    $message = $appConf['error_message'];
                }
                $response->status($httpCode);
                $response->header('Content-Type', 'application/json; charset=utf-8');
                $exception = App::error()->exception($e);
                $controller = App::service()->get(Controller::class);
                $data = $controller->result($exception, $message, $e->getCode() < 200 ? 500 : $e->getCode());
                $data['time'] = $requestTime;
                $data['trace_id'] = $requestTraceId;
                App::middleware()->runAfter($req, $data, $controller->getAfterMiddleware());
                if($appConf['show_error_msg'] == false){
                    $tmpData = $data;
                    unset($tmpData['data']);
                    $response->end(json_encode($tmpData));
                }else{
                    $response->end(json_encode($data));
                }
            }
        });

        $server->on('Start', function ($server) {
            $msg = "服务器事件:后台管理系统触发了启动";
            App::robot()->sendMsg($msg);
            swoole_error_log(SWOOLE_LOG_WARNING, $msg);
            //echo date('Y-m-d H:i:s').'###[event]###'." Start".PHP_EOL;
            // onStart 回调中，仅允许 echo、打印 Log、修改进程名称。不得执行其他操作 (不能调用 server 相关函数等操作，因为服务尚未就绪)。onWorkerStart 和 onStart 回调是在不同进程中并行执行的，不存在先后顺序。
        });

        $server->on('BeforeShutdown', function ($server) {
            //echo date('Y-m-d H:i:s').'###[event]###'." BeforeShutdown".PHP_EOL;
        });

        $server->on('Shutdown', function ($server) {
            App::robot()->sendMsg("服务器事件:后台管理系统触发了正常停止");
            swoole_error_log(SWOOLE_LOG_WARNING, $msg);
        });

        $server->on('WorkerStart', function ($server, int $workerId) {
            App::clean(); // 这样清理掉的话通道会不会端开？
            // 一次性载入所有路由
            $routePath = App::routePath().DIRECTORY_SEPARATOR.'admin';
            $files = scandir($routePath);
            foreach ($files as $k => $v){
                $pathinfo = pathinfo($v);
                if($pathinfo['extension'] == 'php'){
                    App::route()->setVersion($pathinfo['filename']);
                    $routeFile = App::routePath() .DIRECTORY_SEPARATOR.'admin/'. $v;
                    require_once $routeFile; // 事先载入路由
                }
            }

            CacheManager::init();
            DbManager::init();
            App::log()->execute(); // 日志通道运行起来
        });

        $server->on('WorkerStop', function ($server, int $workerId) {
            \Swoole\Timer::clearAll();
            //echo date('Y-m-d H:i:s').'###[event]###'." onWorkerStop".PHP_EOL;
        });

        $server->on('WorkerExit', function ($server, int $workerId) {
            //echo date('Y-m-d H:i:s').'###[event]###'." WorkerExit".PHP_EOL;
        });

        $server->on('Connect', function ($server, int $fd, int $reactorId) {
            //echo date('Y-m-d H:i:s').'###[event]### '.$fd." Connect".PHP_EOL;
        });

        $server->on('Receive', function ($server, int $fd, int $reactorId, string $data) {
            //echo date('Y-m-d H:i:s').'###[event]### '.$fd." Receive".PHP_EOL;
        });

        $server->on('Packet', function ($server, string $data, array $clientInfo) {
            //echo date('Y-m-d H:i:s').'###[event]###'." Packet".PHP_EOL;
        });

        $server->on('Close', function ($server, int $fd, int $reactorId) {
            //echo date('Y-m-d H:i:s').'###[event]### '.$fd." Close".PHP_EOL;
        });

        $server->on('WorkerError', function ($server, int $worker_id, int $worker_pid, int $exit_code, int $signal) {
            $msg = "服务器事件:后台管理系统进程发生错误,worker_id:".$worker_id.',worker_pid:'.$worker_pid.',exit_code:'.$exit_code.',signal:'.$signal;
            App::robot()->sendMsg($msg);
            swoole_error_log(SWOOLE_LOG_ERROR, $msg);
        });

        //处理异步任务(此回调函数在task进程中执行)
        $server->on('Task', function (\Swoole\Server $serv, \Swoole\Server\Task $task) {
            // 这是一个新的进程需要先注册
            $command = $task->data;
            // 给到一个函数名就行看
            if($command && is_string($command)){
                App::task()->init($serv, $this->config['task']);
                try {
                    App::task()->call($command); // 不能在自身里面投
                }catch (\Throwable $e){
                    echo date('Y-m-d H:i:s').'###[fatal]###'." task出错".$e->getMessage().PHP_EOL;
                }
            }

        });

        //处理异步任务的结果(此回调函数在worker进程中执行)
        $server->on('Finish', function ($serv, $task_id, $data) {
            //echo date('Y-m-d H:i:s').'###[event]###'." Finish".PHP_EOL;
        });

        // reload完成后触发一次
        $server->on('AfterReload', function ($serv){
            $msg = '服务器事件:后台管理系统触发了热更新';
            App::robot()->sendMsg($msg);
            swoole_error_log(SWOOLE_LOG_WARNING, $msg);
        });

    }

    /**
     * 用例提示
     * @return void
     */
    protected function usage(){
        echo PHP_EOL.' 用法：php cmd.php server:admin {start | stop | reload | restart | status } '.PHP_EOL;
        echo PHP_EOL.' 启动示例：php cmd.php server:admin start false false代表非守护进程方式启动 )'.PHP_EOL;
        echo PHP_EOL.' 关于环境：请修改全局环境变量 APP_ENV=dev或者APP_ENV=production 没有指定将会载入dev环境文件'.PHP_EOL;
        echo PHP_EOL." 开机启动: linux上将启动命令添加到 /ect/rc.local 文件中, 或参考：https://blog.csdn.net/hualinger/article/details/125321966".PHP_EOL;
        die;
    }


}