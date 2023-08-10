<?php
// +----------------------------------------------------------------------
// | flow-course / Help.php    [ 2021/10/28 2:26 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\console\command;

use zhanshop\App;
use zhanshop\console\Command;
use zhanshop\console\Input;
use zhanshop\console\Output;

class AnnotationRoute extends Command
{

    public function configure()
    {
        $this->setTitle('注解生成路由')->setDescription('一键生成基于控制器配置的注解路由');
    }

    public function execute(Input $input, Output $output)
    {
        $apiDir = App::appPath().DIRECTORY_SEPARATOR.'api';
        $controllerFiles = glob($apiDir.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'controller'.DIRECTORY_SEPARATOR.'*.php');
        foreach($controllerFiles as $k => $v){

        }
    }
}
