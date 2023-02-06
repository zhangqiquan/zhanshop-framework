<?php
// +----------------------------------------------------------------------
// | zhanshop-php / Rpc.php [ 2023/2/2 下午9:59 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\console\command;

use zhanshop\console\Command;
use zhanshop\console\Input;
use zhanshop\console\Output;

class Rpc extends Command
{
    public function configure(){
        $this->setTitle('启动rpc服务')->setDescription('使用该命令可以创建一个rpc服务器');
    }

    public function execute(Input $input, Output $output){

    }
}