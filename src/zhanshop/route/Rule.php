<?php
declare (strict_types = 1);

namespace zhanshop\route;

/**
 * 路由分组类
 */
class Rule
{
    /**
     * 路由绑定
     * @var array
     */
    protected $bind = [];

    /**
     * 当前组
     * @var Group null
     */
    protected $currentGroup = null;
    /**
     * 当前appName
     * @var string
     */
    protected $currentAppName = '';

    /**
     * 当前AppVersion
     * @var string
     */
    protected $currentAppVersion = '';
    /**
     * 当前uri
     * @var string
     */
    protected $currentUri = '';

    /**
     * 当前APP的全局中间件
     * @var array
     */
    protected array $globalMiddleware = [];

    /**
     * 设置当前路由所属app
     * @param string $name
     * @param string $version
     * @return void
     */
    public function setApp(string $name, string $version, array $middleware = []){
        $this->currentAppName = $name;
        $this->currentAppVersion = $version;
        $this->globalMiddleware = $middleware;
    }

    public function setGroup(mixed &$group){
        $this->currentGroup = $group;
    }


    /**
     * 设置路由
     * @param array $methods
     * @param string $uri
     * @param array $handler
     * @return void
     */
    public function addRule(string $uri, array &$handler, string $method = "GET") :Rule{
        $prefix = $this->currentGroup->getPrefix();
        $this->currentUri = $prefix ? $prefix.'.'.$uri : $uri;
        $handler[1] = ucfirst($handler[1]);
        $this->bind[$this->currentAppName][$this->currentAppVersion][$this->currentUri][$method] = [
            'method' => $method,
            'handler' => $handler,
            'service' => [str_replace('\\controller\\', '\\service\\', $handler[0]).'Service', ucfirst($handler[1])],
            'middleware' => array_merge($this->currentGroup->getMiddleware(), $this->globalMiddleware),
            'cache' => $this->currentGroup->getCache(),
            'extra' => [],
            'validate' => [],
            'cross_domain' => $this->currentGroup->getCrossDomain()
        ];
        return $this; // 测试分组路由
    }

    /**
     * 额外参数
     * @param array $params
     * @return void
     */
    public function extra(array $params) :Rule{
        $this->bind[$this->currentAppName][$this->currentAppVersion][$this->currentUri]['extra'] = $params;
        return $this;
    }

    /**
     * 绑定一个入参验证
     * @param string $class
     * @return void
     */
    public function validate(array $validate) :Rule{
        $this->bind[$this->currentAppName][$this->currentAppVersion][$this->currentUri]['validate'] = $validate;
        return $this;
    }

    /**
     * 设置中间件
     * @param array $class
     * @return void
     */
    public function middleware(array $class) :Rule{
        $this->bind[$this->currentAppName][$this->currentAppVersion][$this->currentUri]['middleware'] = array_merge($class, $this->bind[$this->currentAppName][$this->currentAppVersion][$this->currentUri]['middleware']);
        return $this;
    }

    /**
     * 设置header请求头缓存
     * @param int $time
     * @return void
     */
    public function cache(int $time) :Rule{
        $this->bind[$this->currentAppName][$this->currentAppVersion][$this->currentUri]['cache'] = $time;
        return $this;
    }

    public function crossDomain(array $option){
        $this->bind[$this->currentAppName][$this->currentAppVersion][$this->currentUri]['cross_domain'] = array_merge($option, $this->currentGroup->getCrossDomain());
    }

    /**
     * 清空分组下的路由规则
     * @access public
     * @return void
     */
    public function clear(): void
    {
        $this->bind = [];
    }

    /**
     * 获取已绑定的路由
     * @param $name
     * @param $version
     * @param $uri
     * @return array|mixed
     */
    public function getBind(string &$name, string &$version,  string &$uri){
        return $this->bind[$name][$version][$uri] ?? [];
    }

    /**
     * 获取所有路由
     * @return array
     */
    public function getAll(){
        return $this->bind;
    }
}
