<?php
// +----------------------------------------------------------------------
// | zhanshop-php / HttpReload.php    [ 2023/2/2 11:23 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\console\task;
use zhanshop\App;
use zhanshop\console\WatchCode;
use zhanshop\Log;

class WatchServTask
{
    protected static $init = false;
    protected static $version = '';

    protected static function version(){
        $version = '';
        try {
            $config = include App::rootPath().DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'app.php';
            $version = $config['app_version'] ?? '';
        }catch (\Throwable $e){

        }
        return $version;
    }
    // 记录版本
    public static function init(){
        if(self::$init == false){
            self::$version = self::version();
            WatchCode::init(App::rootPath());
            self::$init = true;
        }
    }

    /**
     * @param \Swoole\Http\Server $serv
     * @return void
     */
    public static function check(){
        clearstatcache();
        self::init();
        if(WatchCode::isChange()){
            $isReload = (self::$version == self::version());
            self::$init = false;
            self::init();
            if($isReload){
                Log::errorLog(SWOOLE_LOG_TRACE, '重新载入和更新工作进程'.PHP_EOL);
                App::task()->getServer()->reload();
            }else{
                Log::errorLog(SWOOLE_LOG_TRACE, '重启更新整个项目的所有进程'.PHP_EOL);
                $daemonize = $_SERVER['argv'][4] ?? 'true';
                if($daemonize === 'true'){
                    $command = 'nohup '.$_SERVER['_'].' '.App::rootPath().DIRECTORY_SEPARATOR.$_SERVER['argv'][0].' '.$_SERVER['argv'][1].' '.$_SERVER['argv'][2].' restart'.($daemonize == 'false' ? ' false' : '');
                    $log = App::runtimePath().DIRECTORY_SEPARATOR.'restart.log';
                    $command .= ' > '.$log.' 2>&1 &';
                    \Swoole\Coroutine\System::exec($command);
                }else{
                    Log::errorLog(SWOOLE_LOG_TRACE, "server需要手动重新启动".PHP_EOL);
                    $command = 'nohup '.$_SERVER['_'].' '.App::rootPath().DIRECTORY_SEPARATOR.$_SERVER['argv'][0].' '.$_SERVER['argv'][1].' '.$_SERVER['argv'][2].' stop'.($daemonize == 'false' ? ' false' : '');
                    $log = App::runtimePath().DIRECTORY_SEPARATOR.'restart.log';
                    $command .= ' > '.$log.' 2>&1 &';
                    \Swoole\Coroutine\System::exec($command);
                }
            }

        }
    }
}