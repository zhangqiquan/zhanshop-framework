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

use app\admin\v4_0_0\middleware\Test;
use zhanshop\cache\CacheManager;
use zhanshop\console\command\Server;
use zhanshop\database\DbManager;
use zhanshop\route\Dispatch;

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
            $appInfo = pathinfo($v);
            $middleware = $middlewares[$appInfo['filename']] ?? [];
            foreach ($files as $kk => $vv){
                $versionInfo = pathinfo($vv);
                if($versionInfo['extension'] == 'php'){
                    App::route()->getRule()->setApp($appInfo['filename'], $versionInfo['filename'], $middleware);
                    $routeFile = App::routePath() .DIRECTORY_SEPARATOR.$v.'/'. $vv;
                    require_once $routeFile; // 事先载入路由
                }
            }
        }
    }

    /**
     * 执行前置中间件
     * @param Request $request
     * @param Response $servResponse
     * @return void
     */
    public function beforeMiddleware(Request &$request, Response &$servResponse){
        // 执行前置中间件
        foreach ($request->getRoure()['middleware'] as $k => $v){
            if(!property_exists($v, 'isAfter')){
                App::make($v)->handle($request, $servResponse);
                unset($request->getRoure()['middleware'][$k]);
            }
        }
    }

    /**
     * 执行后置换中间件
     * @param Request $request
     * @param Response $servResponse
     * @return void
     */
    public function afterMiddleware(Request &$request, Response &$servResponse){
        foreach ($request->getRoure()['middleware'] as $k => $v){
            App::make($v)->handle($request, $servResponse);
        }
    }

    /**
     * 只执行全局中间件
     * @param Request $request
     * @param Response $servResponse
     * @return void
     */
    public function globalAfterMiddleware(string &$appName, Request &$request, Response &$servResponse){
        $middleware = App::config()->get('middleware.'.$appName, []);
        foreach ($middleware as $v){
            App::make($v)->handle($request, $servResponse);
        }
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

            // 执行前置中间件
            $this->beforeMiddleware($request, $servResponse);

            $data = $dispatch->run($appName, $request, $servResponse);

            $servResponse->setData($data);
            // 执行后置中间件
            $this->afterMiddleware($request, $servResponse);
        }catch (\Throwable $e){
            $servResponse->setStatus((int)$e->getCode());
            $data = $this->getErrorData($appName, $e);
            $servResponse->setData($data); // 先执行后置中间件
            // 执行全局的中间件全部变成了后置
            $this->globalAfterMiddleware($appName, $request, $servResponse);
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
            $data = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ];
            $data = $controller->result($data, $e->getMessage(), $e->getCode());
        }catch (\Throwable $e){
            //Log::errorLog(SWOOLE_LOG_ERROR, $e->getMessage().PHP_EOL.'#@ '.$e->getFile().':'.$e->getLine().PHP_EOL.$e->getTraceAsString());
            $data = $e->getMessage();
        }
        return $data;
    }
}