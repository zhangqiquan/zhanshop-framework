<?php
// +----------------------------------------------------------------------
// | flow-course / App类    [ 2021/10/22 4:45 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop;
/**
 * @method static Config config()
 * @method static Cache cache()
 * @method static RequestCache requestCache()
 * @method static Log log()
 * @method static Error error()
 * @method static Validate validate()
 * @method static Route route()
 * @method static Env env()
 * @method static Service service()
 * @method static Database database()
 * @method static Aes aes()
 * @method static Rsa rsa()
 * @method static Middleware middleware()
 * @method static Console console()
 * @method static Task task()
 */
class App extends Container
{
    /**
     * 应用调试模式
     * @var bool
     */
    protected static $appDebug = false;

    /**
     * 应用开始时间
     * @var float
     */
    protected static $beginTime;

    /**
     * 应用内存初始占用
     * @var integer
     */
    protected static $beginMem;
    /**
     * 应用根目录
     * @var string
     */
    protected static $rootPath = '';

    /**
     * 框架目录
     * @var string
     */
    protected static $kernelPath = '';

    /**
     * 应用目录
     * @var string
     */
    protected static $appPath = '';

    /**
     * Runtime目录
     * @var string
     */
    protected static $runtimePath = '';

    /**
     * 路由定义目录
     * @var string
     */
    protected static $routePath = '';
    /**
     * app注册,其他的也需要注册,service,lib都可以搞一套注册 使用的时候注册
     * 应用初始化器(从app容器调用的类全部是单例)
     * @var array
     */
    protected $registers = [
        'error'           => Error::class,
        'env'             => Env::class,
        'config'          => Config::class,
        'cache'           => Cache::class,
        'log'             => Log::class,
        'task'            => Task::class,
        'requestCache'    => RequestCache::class,
        'route'           => Route::class,
        'validate'        => Validate::class,
        'database'        => Database::class,
        'console'         => Console::class,
        'service'         => Service::class,
        'middleware'      => Middleware::class,
        'aes'             => Aes::class,
        'rsa'             => Rsa::class,
    ];

    public static function make(string $rootPath){
        self::$beginTime = microtime(true); // 应用开始时间
        self::$beginMem  = memory_get_usage(); // 应用开始内存

        self::$kernelPath   = __DIR__;
        self::$rootPath     = $rootPath;
        self::$appPath      = self::$rootPath .DIRECTORY_SEPARATOR. 'app';
        self::$runtimePath  = self::$rootPath .DIRECTORY_SEPARATOR. 'runtime';
        self::$routePath    = self::$rootPath .DIRECTORY_SEPARATOR. 'route';
        self::$instance = new static();

        App::config();

        App::error();

        set_error_handler([App::error(), 'error'], E_ALL);// 处理运行中的错误 比如下标错误等
        set_exception_handler([App::error(), 'exception']); // 设置用户自定义的异常处理函数throw new \Exception($msg, $code);

        return self::$instance;
    }

    /**
     * 获取框架目录地址
     * @return string
     */
    public static function kernelPath(){
        return self::$kernelPath;
    }

    /**
     * 获取项目跟目录地址
     * @return string
     */
    public static function rootPath(){
        return self::$rootPath;
    }

    /**
     * 获取app跟目录地址
     * @return string
     */
    public static function appPath(){
        return self::$appPath;
    }

    /**
     * 获取运行目录地址
     * @return string
     */
    public static function runtimePath(){
        return self::$runtimePath;
    }

    /**
     * 获取路由目录地址
     * @return string
     */
    public static function routePath(){
        return self::$routePath;
    }

    /**
     * 获取app调试状态
     * @return string
     */
    public static function appDebug(){
        return self::$appDebug;
    }

    /**
     * 获取框架开始时间
     * @return string
     */
    public static function beginTime(){
        return self::$beginTime;
    }

    /**
     * 获取框架开始内存
     * @return string
     */
    public static function beginMem(){
        return self::$beginMem;
    }

}