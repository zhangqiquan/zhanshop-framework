<?php
// +----------------------------------------------------------------------
// | flow-course / Route.php    [ 2021/10/29 2:46 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace zhanshop\console\command\apicreate;


use zhanshop\App;
use zhanshop\console\Input;
use zhanshop\Helper;

class Route
{
    protected static $appType = 'http';
    public static function create(Input $input, string $appType){
        self::$appType = $appType;
        $version = $input->param('version');
        // 驼峰转下划线
        $uri = '/'.Helper::uncamelize($input->param('class')).'.'.Helper::uncamelize($input->param('method'));
        $input->offsetSet('uri', $uri);
        $reqtype = $input->param('reqtype');
        self::check($version, $reqtype, $version, $uri);
        // 追入路由对象 即将要写入的新路由
        App::route()->setVersion($version);
        App::route()->match($reqtype, $uri, $input->param('class').'@'.$input->param('method'));

        // 写路由文件
        self::write($version);

        echo PHP_EOL."路由生成成功".PHP_EOL;
    }

    // 检查这个路由是否存在
    public static function check(string $version, array $methods, string $uri, string $action){

        $routeFile = App::rootPath() .DIRECTORY_SEPARATOR. 'route' . DIRECTORY_SEPARATOR.self::$appType .DIRECTORY_SEPARATOR. $version . '.php';
        if(!file_exists($routeFile)) self::createRouteFile($version);

        include $routeFile;
        $routes = App::route()->getAll();
        if(isset($routes[$version][$uri])){
            echo "???【警告！当前输入的uri已经存在】???";
        }
    }

    /**
     * 重写路由文件
     */
    public static function write(string $version){
        // 对路由进行排序
        $all = App::route()->getAll();
        //print_r($all);die;
        $all[$version] = $all[$version] ?? [];
        if($all[$version]) ksort($all[$version]);
        $fileData = Helper::headComment('路由文件')."use zhanshop\App;\n\n";
        foreach($all[$version] as $v){
            $method = strtoupper("'".implode("','", $v[0])."'");
            $fileData .= "App::route()->match([".$method."], '".$v[1]."', '".$v[2]."');\n";
        }
        // 写新的有问题老的没有问题
        self::createRouteFile($version, $fileData); // 写入路由文件
    }

    /**
     * 写入路由文件
     * @param string $version
     * @param string|null $code
     */
    public static function createRouteFile(string $version, ?string $code = null){
        file_put_contents(App::rootPath().DIRECTORY_SEPARATOR.'route'.DIRECTORY_SEPARATOR.self::$appType.DIRECTORY_SEPARATOR.$version.'.php', $code ?? '<?php'.PHP_EOL);
    }
}