<?php
// +----------------------------------------------------------------------
// | admin / Library.php    [ 2023/6/19 下午4:50 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\console\command;

use zhanshop\App;
use zhanshop\console\Command;
use zhanshop\console\Input;
use zhanshop\console\Output;

class Phar extends Command
{

    public function configure()
    {
        $this->setTitle('phar打包')->setDescription('将vendor项目目录打包成phar并存放在项目中');
    }

    /**
     * 打包
     * @param $pharPath
     * @param $sourceDir
     * @return void
     * @throws \Exception
     */
    protected function phar($pharPath, $sourceDir){
        if(ini_get('phar.readonly')){
            App::error()->setError("使用Phar需要先在php.ini设置 phar.readonly = Off");
        }

        $phar = new \Phar($pharPath);
        $phar->buildFromIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS)),
            $sourceDir
        );
        $phar->compressFiles(\Phar::GZ);

        $phar->addFile(__DIR__.DIRECTORY_SEPARATOR.'pharInit.php', 'index.php');

        $phar->stopBuffering();
    }

    public function execute(Input $input, Output $output)
    {
        $input->input('vendor', 'vendor目录');
        $input->input('pharName', '生成的phar名');
        $vendor = $input->param('vendor');
        $pharName = $input->param('pharName');
        $pharPath = App::appPath().DIRECTORY_SEPARATOR.'library'.DIRECTORY_SEPARATOR.$pharName.'.phar';

        $this->phar($pharPath, $vendor);
    }
}