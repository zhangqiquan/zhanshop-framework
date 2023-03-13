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

class Help extends Command
{

    public function configure()
    {
        $this->setTitle('帮助')->setDescription('显示命令的帮助');
    }

    public function execute(Input $input, Output $output)
    {
        // 拿到当前所有注册的控制台
        $commands = App::console()->getCommands();

        echo PHP_EOL;
        echo "欢迎使用zhanshop控制台系统".PHP_EOL;

        echo PHP_EOL;
        echo "用法:   cmd 指令 --参数 参数信息".PHP_EOL;
        $output->output("");
        $output->output("可用命令：", 'info');
        $output->output("");
        foreach($commands as $k => $v){
            $output->output($k, 'success', false);
            $output->output(str_repeat(" ", 36 - strlen($k)), 'success', false);
            //最大20个空格
            $description = '';
            try {
                $obj = new $v();
                $obj->configure();
                $description .= $obj->getTitle().' - '.$obj->getDescription();
                $output->output($description);
            }catch (\Throwable $exception){
                $output->output($exception->getMessage().' '.$exception->getFile().':'.$exception->getLine(), 'error');
            }

        }
    }
}