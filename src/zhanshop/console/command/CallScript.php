<?php

namespace zhanshop\console\command;

use zhanshop\App;
use zhanshop\console\Command;
use zhanshop\console\Input;
use zhanshop\console\Output;
use zhanshop\Log;

class CallScript extends Command
{
    public function configure()
    {
        $this->setTitle('执行脚本')->setDescription('单独启动进程方式启动指定程序');
    }

    public function execute(Input $input, Output $output)
    {
        $file = $input->param('file');
        if($file == false) App::error()->setError("--file不能为空");
        $param = $input->getArgv();
        unset($param[0], $param[1]);
        $param = array_values($param);
        self::start($file, $param, function ($event, $body){
            print_r([$event, $body]);
        });
    }

    /**
     * 启动脚本
     * @param $phpFile
     * @param array $callBack
     * @return void
     */
    public static function start(string $phpFile, array $param, callable $callBack)
    {
        $cmd = $phpFile.' '.implode(' ', $param);
        $error = App::runtimePath().'/process/'.md5($cmd).'.log';
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("file", $error, "a")
        );

        $process = proc_open($cmd, $descriptorspec, $pipes);
        if($process == false) App::error()->setError($cmd.'启动失败');
        $status = true;
        while ($status) {
            $msg = fread($pipes[1], 20480);
            if($msg){
                $arr = explode("\r\n", $msg);
                foreach($arr as $v){
                    $arr = json_decode($v, true);
                    $event = $arr['event'] ?? "unknown";
                    $callBack($event, $arr["body"] ?? $v);
                }
            }else if($msg === ""){
                $status = false;
                // 进程终止
                $msg = file_get_contents($error);
                $callBack("close", $msg);
                break;
            }
        }
        @unlink($error);
        fclose($pipes[0]);
        proc_close($process);
    }
}