<?php
// +----------------------------------------------------------------------
// | zhanshop_admin / Web.php [ 2023/4/28 下午8:35 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop;

use zhanshop\cache\CacheManager;
use zhanshop\database\DbManager;
use zhanshop\route\Dispatch;
use zhanshop\service\ApiDoc;

class WebHandle
{
    /**
     * 当前服务事件类
     * @var ServEvent
     */
    protected mixed $servEvent;

    /**
     * 构造函数
     * @param array $servNames
     */
    public function __construct(mixed $servEvent = null)
    {
        $this->servEvent = $servEvent;

        $this->loadRoute(); // 装载路由配置
        App::task($this->servEvent->server ?? null); // 载入task类
        CacheManager::init(); // 缓存管理初始化
        DbManager::init(); // 数据库管理初始化
        App::log($this->servEvent->setting['daemonize'] ?? false)->execute(); // 日志通道运行起来
    }

    /**
     * 载入路由配置
     * @return void
     */
    protected function loadRoute(){
        $middlewares = App::config()->get('middleware', []);
        foreach($this->servEvent->servNames ?? [] as $v){
            $routePath = App::routePath().DIRECTORY_SEPARATOR.$v;
            if(!file_exists($routePath)) continue;
            $files = scandir($routePath);
            $middleware = $middlewares[$v] ?? [];
            foreach ($files as $kk => $vv){
                $versionInfo = pathinfo($vv);
                if($versionInfo['extension'] == 'php'){
                    App::route()->getRule()->setApp($v, $versionInfo['filename'], $middleware);
                    $routeFile = App::routePath() .DIRECTORY_SEPARATOR.$v.'/'. $vv;
                    require_once $routeFile; // 事先载入路由
                }
            }

            App::route()->getRule()->setApp($v, 'v1', []);
            App::route()->rule('GET', '/api.doc', [ApiDoc::class, 'call'])->extra([$v]);
            App::route()->rule('POST', '/api.doc', [ApiDoc::class, 'call'])->extra([$v]);
        }
        App::route()->sortMiddleware(); // 对中间件进行倒序
    }

    public function middleware(Request &$request, \Closure $next){
        return array_reduce(
            $request->getRoure()['middleware'],
            $this->carry(),
            $next
        );
    }

    protected function carry()
    {
        /**
         * @$stack 上一次中间件对象
         * @$pipe 当前中间件对象
         */
        return function ($stack, $pipe) {
            /**
             * @$passable request请求对象
             */
            return function (Request &$request) use ($stack, $pipe) {
                try {
                    return $pipe($request, $stack);
                } catch (Throwable $e) {
                    App::error()->setError($e->getMessage(), $e->getCode());
                }
            };
        };
    }

    /**
     * 路由调度
     * @param int $protocol
     * @param Request $request
     * @return void
     */
    public function dispatch(string $appName, Request &$request, Response &$servResponse){
        try {
            $dispatch = App::make(Dispatch::class);

            $dispatch->check($appName, $request);

            $handler = $request->getRoure()['handler'];
            $controller = $handler[0];
            $action = $handler[1];
                //print_r($handler);die;
            // 开始执行中间件

            // 执行前置中间件
            $dispatch = $this->middleware($request, function (&$request) use (&$controller, &$action, &$servResponse){
                $data = App::make($controller)->$action($request, $servResponse);
                $servResponse->setData($data);
            });

            $dispatch($request);
        }catch (\Throwable $e){
            $servResponse->setStatus((int)$e->getCode());
            $data = $this->getErrorData($appName, $e);
            $servResponse->setData($data); // 先执行后置中间件

            // 执行全局的中间件全部变成了后置
            //$this->globalAfterMiddleware($appName, $request, $servResponse);
        }
        // 设置控制器的基类
        $servResponse->setController('\\app\\api\\'.$appName.'\\Controller');
    }

    /**
     * 获取错误信息
     * @param \Throwable $e
     * @return string
     */
    public function getErrorData(string &$appName, \Throwable &$e){
        try {
            $controller = App::make('\\app\\api\\'.$appName.'\\Controller');
            $code = $e->getCode();
            $data = [];
            // 404错误不抛出错误详情
            if($code != 404){
                $data = [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTrace(),
                ];
            }
            $data = $controller->result($data, $e->getMessage(), $code);
        }catch (\Throwable $e){
            //Log::errorLog(SWOOLE_LOG_ERROR, $e->getMessage().PHP_EOL.'#@ '.$e->getFile().':'.$e->getLine().PHP_EOL.$e->getTraceAsString());
            $data = $e->getMessage();
        }
        return $data;
    }
}