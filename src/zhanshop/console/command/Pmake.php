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

class Pmake extends Command
{

    public function configure()
    {
        $this->setTitle('构建')->setDescription('构建可运行php二进制包');
    }

    public function execute(Input $input, Output $output)
    {
        $argv = $input->getArgv();
        if($argv == false) return $this->usage();

        $saveName = $argv[0];
        if(pathinfo($saveName, PATHINFO_EXTENSION) != 'phar') $saveName .= '.phar';
        $env = $argv[1] ?? 'local';

        $fileName = basename($saveName);
        $phar = new \Phar($saveName, 0, $fileName);

        $phar->startBuffering();
        $phar->buildFromIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator('./', \FilesystemIterator::SKIP_DOTS)),
            './'
        );

        $phpBin = 'php';
        if($env == 'local') $phpBin = $_SERVER['_'];
        $stub = $phar->createDefaultStub('./cmd.php', 'cmd.php');

        $topCode = '<?php'.PHP_EOL;
        $topCode .= '$_SERVER["SCRIPT_IS_PHAR"] = 1;'.PHP_EOL;
        $topCode .= '$_SERVER["APP_ENV"] = "'."AAA".'";'.PHP_EOL.'?>'.PHP_EOL.PHP_EOL;

        $stub = "#!/usr/bin/env ".$phpBin."\n" .PHP_EOL.$topCode. $stub;
        $phar->compressFiles(\Phar::GZ);
        $phar->setStub($stub);
        $phar->stopBuffering();
        chmod($saveName, 755);
    }

    protected function usage(){
        echo PHP_EOL.' 用法：php cmd.php pmake 程序名称 源目录 dev cmd.php v1.0.0'.PHP_EOL;
        die;
    }
}