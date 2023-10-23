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

use app\exception\HttpException;
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
        $dispatch = App::make(Dispatch::class);
        foreach($this->servEvent->servNames ?? [] as $v){
            $routePath = App::routePath().DIRECTORY_SEPARATOR.$v;
            if(!file_exists($routePath)) continue;
            $files = scandir($routePath);
            foreach ($files as $kk => $vv){
                $versionInfo = pathinfo($vv);
                if($versionInfo['extension'] == 'php'){
                    $routeFile = App::routePath() .DIRECTORY_SEPARATOR.$v.'/'. $vv;
                    $dispatch->setApp($v);
                    $dispatch->setVersion($versionInfo['filename']);
                    require_once $routeFile; // 事先载入路由
                }
            }
            $dispatch->setApp($v);
            $dispatch->setVersion('v1');
            App::route()->rule('GET', '/api.doc', [ApiDoc::class, 'call'])->extra([$v]);
            App::route()->rule('POST', '/api.doc', [ApiDoc::class, 'call'])->extra([$v]);

            App::route()->rule('POST', '/git.push', [ApiDoc::class, 'call'])->extra([$v]);
        }
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


            $dispatch = $this->middleware($request, function (&$request) use (&$controller, &$action, &$servResponse){
                $data = App::make($controller)->$action($request, $servResponse);
                $servResponse->setData($data);
                return $servResponse;
            });

            $dispatch($request);
        }catch (\Throwable $e){
            $servResponse->setHttpCode((int)$e->getCode());
            //$servResponse->se
            $data = App::make(HttpException::class)->handle($request, $servResponse, $e);
            $servResponse->setData($data); // 先执行后置中间件
        }
    }
}