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
        $this->init($input);

        // 创建路由
        Route::create($input);

        // 创建控制器
        Controller::create($input);

        // 创建service
        Service::create($input);

        // 创建数据模型
        Model::create($input);

        // 创建api文档
        Doc::create($input);

        $output->output(PHP_EOL.PHP_EOL.'【ok】后面的代码请手动完善！！！', 'success');
    }

    /**
     * 初始化接收参数
     * @param \kernel\console\Input $input
     * @return Input
     */
    protected function init(Input $input){
        //return $this->test($input); // 测试参数
        $input->input('title', '当前api名称', '测试');
        $input->input('groupname', '当前api所属组', '测试分组');
        $input->input('version', '当前api控制器的版本号', 'v1.0.0');
        $input->input('class', '当前api控制器的类名', 'Test');
        $input->input('method', '当前api控制器的方法', 'test');
        $input->input('table', '当前api控制器服务关联的数据库表名', '');
        $input->input('reqtype', '请求方法【多个使用,分割】', 'get,post,put,delete');
        //$input->input('uri', '当前控制器对应的uri地址');
        // 类名 首字母大写 驼峰命名
        $input->offsetSet('class', ucwords(Helper::camelize($input->param('class'))));
        // 方法名 首字母小写 驼峰命名
        $input->offsetSet('method', Helper::camelize($input->param('method')));
        // 请求类型全部小写
        $input->offsetSet('reqtype', explode(',', strtolower($input->param('reqtype'))));
        return $input;
    }

    /**
     * 仅用于测试的参数
     * @param Input $input
     */
    protected function test(Input $input){
        $input->offsetSet('class', 'User');
        $input->offsetSet('title', '用户信息');
        $input->offsetSet('groupname', '用户中心');
        $input->offsetSet('version', 'v3.0.0');
        $input->offsetSet('method', 'info');
        $input->offsetSet('table', 'user_member');
        $input->offsetSet('reqtype', ['get', 'post', 'delete', 'put']);
    }
}