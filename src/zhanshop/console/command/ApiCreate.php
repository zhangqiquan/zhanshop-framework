<?php
// +----------------------------------------------------------------------
// | zhanshop-php / ApiCreate.php    [ 2023/1/5 12:22 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\console\command;


use zhanshop\Helper;
use zhanshop\console\command\apicreate\Controller;
use zhanshop\console\command\apicreate\Doc;
use zhanshop\console\command\apicreate\Model;
use zhanshop\console\command\apicreate\Route;
use zhanshop\console\command\apicreate\Service;
use zhanshop\console\Command;
use zhanshop\console\Input;
use zhanshop\console\Output;

class ApiCreate extends Command
{
    public function configure()
    {
        $this->setTitle('api构建')->setDescription('一键生成http接口');
    }

    public function execute(Input $input, Output $output){

        $all = $input->getArgv();

        if($all == false){
            $output->output("可用命令参考:");
            return $this->help();
        }


        $this->init($input, $all[0]);

        // 创建路由
        Route::create($input, $all[0]);

        // 创建控制器
        Controller::create($input, $all[0]);

        // 创建service
        Service::create($input, $all[0]);

        $output->output(PHP_EOL.PHP_EOL.'【ok】后面的代码请手动完善！！！', 'success');
    }

    /**
     * 初始化接收参数
     * @param \kernel\console\Input $input
     * @return Input
     */
    protected function init(Input $input, string $appName){
        $this->inputTitle($input, $appName);
        $this->inputGroupname($input, $appName);
        $this->inputVersion($input, $appName);
        $this->inputClass($input, $appName);
        $this->inputAction($input, $appName);
        $this->inputMethod($input, $appName);
        return $input;
    }

    /**
     * 获取api标题
     * @param Input $input
     * @return void
     */
    protected function inputTitle(Input $input, $appName){
        $input->input('title', '新建api名称');
        if($input->param('title') == false){
            return $this->inputTitle($input, $appName);
        }
    }

    /**
     * 获取api分组
     * @param Input $input
     * @return void
     */
    protected function inputGroupname(Input $input, $appName){
        $input->input('groupname', '新建api所属组名称');
        if($input->param('groupname') == false){
            return $this->inputGroupname($input, $appName);
        }
    }

    /**
     * 获取api版本
     * @param Input $input
     * @return void
     */
    protected function inputVersion(Input $input, $appName){
        $input->input('version', '新建api所属版本号');
        $version = $input->param('version');
        if($version == false){
            return $this->inputVersion($input, $appName);
        }

        if($version[0] != 'v'){
            echo PHP_EOL."api版本请使用v开头".PHP_EOL;
            return $this->inputVersion($input, $appName);
        }

        if(isset($version[1]) == false || (isset($version[1]) && is_numeric($version[1]) == false)){
            echo PHP_EOL."api版本请使用v数字开头".PHP_EOL;
            return $this->inputVersion($input, $appName);
        }
    }

    /**
     * 获取控制器类名
     * @param Input $input
     * @return void
     */
    protected function inputClass(Input $input, $appName){
        $input->input('class', '新建api所属控制器类名');
        $class = $input->param('class');
        if($class == false){
            return $this->inputClass($input, $appName);
        }

        if(!preg_match("/^[a-zA-Z\s]+$/", $class[0])){
            echo PHP_EOL."api所属控制器类名首字符必须是字母".PHP_EOL;
            return $this->inputClass($input, $appName);
        }

        if(!ctype_alnum($class[0])){
            echo PHP_EOL."api所属控制器类名只能由字母和数字组成".PHP_EOL;
            return $this->inputClass($input, $appName);
        }


        $version = $input->param('version');
        $input->offsetSet('uri', lcfirst($class)); // 首字母转小写
        $class = '\\app\\api\\'.$appName.'\\'.(str_replace('.', '_', $version)).'\\controller\\'.(ucfirst($input->param('class')));
        $input->offsetSet('class', $class);
    }

    /**
     * 获取控制器方法
     * @param Input $input
     * @return void
     */
    protected function inputAction(Input $input, $appName){
        $input->input('action', '新建api控制器方法名');
        $action = $input->param('action');
        if($action == false){
            return $this->inputAction($input, $appName);
        }

        if(!preg_match("/^[a-zA-Z\s]+$/", $action[0])){
            echo PHP_EOL."api控制器方法名首字符必须是字母".PHP_EOL;
            return $this->inputAction($input, $appName);
        }

        if(!ctype_alnum($action[0])){
            echo PHP_EOL."api控制器方法名只能由字母和数字组成".PHP_EOL;
            return $this->inputAction($input, $appName);
        }
        $action = lcfirst($action);
        $input->offsetSet('action', $action);
        $uri = $input->param('uri');
        $input->offsetSet('uri', '/'.$uri.'.'.$action);
    }

    /**
     * 获取协议请求方法
     * @param Input $input
     * @return void
     */
    protected function inputMethod(Input $input, $appName){
        $input->input('method', '协议请求支持方法默认get,post,put,delete', 'get,post,put,delete');
        $method = $input->param('method');
        $method = explode(',', $method);
        $methods = [];
        foreach($method as $v){
            if(!ctype_alnum($v)){
                echo PHP_EOL."协议请求支持方法只能由字母和数字组成".PHP_EOL;
                return $this->inputMethod($input, $appName);
            }
            $methods[] = strtolower($v);
        }


        $input->offsetSet('method', $methods);
    }

    public function help()
    {
        echo 'php cmd.php api:create {http | admin | wss} ' . PHP_EOL;
    }
}
