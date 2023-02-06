<?php
// +----------------------------------------------------------------------
// | zhanshop-php / ApiDocManager.php [ 2023/2/2 下午10:16 ]
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
use zhanshop\service\ApiDocService;

class ApiDocManager extends Command
{
    protected Output $output;
    protected Input $input;
    public function configure()
    {
        $this->setTitle('api管理')->setDescription('对现有的文档数据进行(修改/删除/清空/回滚/生成等功能)');
        // TODO: Implement configure() method.
    }

    public function execute(Input $input, Output $output)
    {
        $this->input = $input;
        $this->output = $output;
        $all = $input->getArgv();
        if($all == false){
            $output->output("可用命令参考:");
            return $this->help();
        }
        $method = $all[0];
        //$method = $input->input('method', '[help,get,edit,delete,clean,rollback,generate]', 'help');
        $this->service = App::service()->get(ApiDocService::class);
        $this->$method();
    }

    public function clean(){
        App::service()->get(ApiDocService::class)->clean();
    }

    public function edit(){
        $version = $this->input->input('version', '需要更新的api的版本', 'v3.1.0');
        $uri = $this->input->input('uri', '需要更新的api的uri', '/event.play');
        $data = $this->input->input('data', '需要更新的数据,格式为json', '{"detail":"<table  class=\"table table-bordered\"></table>"}');
        $data = @json_decode($data, true);
        if(is_array($data)){
            App::service()->get(ApiDocService::class)->edit($version, $uri, $data);
            return $this->output->output("成功", 'success');
        }
        $this->output->output("更新的数据格式必须json!!!", 'error');
    }

    public function generate(){
        $all = scandir(App::routePath().DIRECTORY_SEPARATOR.'http');
        foreach($all as $v){
            $infos = pathinfo($v);
            if($infos['extension'] == 'php'){
                App::route()->setVersion($infos['filename']);
                $luyouFile = App::routePath().DIRECTORY_SEPARATOR.'http'.DIRECTORY_SEPARATOR.$v;
                include App::routePath().DIRECTORY_SEPARATOR.'http'.DIRECTORY_SEPARATOR.$v;
                try {
                    $this->updateAllApiDoc($infos['filename'], App::route()->getAll($infos['filename']));
                }catch (\Throwable $e){
                    continue;
                }

                App::route()->clean(); // 清理掉
            }
        }
    }

    protected $runNun = 1;

    protected function updateAllApiDoc(string $version, array $data){
        $version_ = str_replace('.', '_' , $version);
        foreach($data as $v){
            echo '###'.$this->runNun++;
            $apiDocData = [];
            $requestTypes = $v[0];
            $action = $v[2];
            $uri = $v[1];
            try {
                $apiDocData['version'] = $version;
                $apiDocData['uri'] = $uri;
                $apiDocData['action'] = $action;
                $apiDocData['method'] = json_encode($requestTypes, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
                $apiDocData['title'] = $this->service->getApiDocTitle($version_, $action); // 拿到apiDoc标题
                $apiDocData['groupname'] = $this->service->getApiDocGroup($version_, $action); // 拿到apiDoc分组
                // 去除所有空格
                if($apiDocData['groupname'] == false){
                    echo '【警告】@apiGroup 分组名称未定义'.$version.'/'.$action;
                    continue;
                    //App::error()->setError('@apiGroup 分组名称未定义'.$version.'/'.$action);
                }
                foreach($requestTypes as $vv){
                    $apiDocData['param'][$vv] = $this->service->getApiDocParam($version_, $action, strtolower($vv)); // 拿到文档参数
                    $apiDocData['explain'][$vv] = $this->service->getApiDocExplain($version_, $action, strtolower($vv)); // 拿到错误代码解析
                }
                $apiDocData['param'] = json_encode($apiDocData['param'], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
                $apiDocData['explain'] = json_encode($apiDocData['explain'], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
                //print_r($apiDocData);
                $this->service->maintain($apiDocData); // 更新维护文档
                $this->output->output(PHP_EOL.'【成功】'.str_replace('_', '.', $version_).$uri, 'success');
            }catch (\Throwable $e){
                echo "【错误】";
                $this->output->exception($e);
            }
            echo PHP_EOL.'======================================================================================'.PHP_EOL;
        }
    }


    public function help(){
        echo 'php cmd.php api:manager edit [编辑api文档]'.PHP_EOL;
        echo 'php cmd.php api:manager delete [删除某条api文档]'.PHP_EOL;
        echo 'php cmd.php api:manager clean [清空api文档]'.PHP_EOL;
        echo 'php cmd.php api:manager rollback [回滚至上一次api文档]'.PHP_EOL;
        echo 'php cmd.php api:manager generate [生成api文档]'.PHP_EOL;
    }

    protected function delete(){
        $version = $this->input->input('version', '删除的版本');
        $uri = $this->input->input('uri', '删除的uri');
        $version ?? App::error()->setError('--version 不能为空');
        $uri ?? App::error()->setError('--uri 不能为空');

        $this->service->delete($version, $uri);
        $this->output->output('删除成功', 'success');
    }

    public function rollback(){
        $this->service->rollback();
        $this->output->output('回滚成功', 'success');
    }

    public function __call(string $name, array $arguments){
        $this->output->output("没有".$name."的指令, 指令参考如下:", 'error');
        $this->help();
    }
}