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
use zhanshop\apidoc\ApiDocService;

class ApiDoc extends Command
{
    protected Output $output;
    protected Input $input;
    protected ApiDocService $service;
    protected $appName = 'all';
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
        $this->appName = $all[1] ?? 'all';
        $this->service = App::make(ApiDocService::class, [$this->appName]);
        $this->$method();
    }

    public function generate(){
        $routePath = App::routePath().DIRECTORY_SEPARATOR.$this->appName;
        if(!file_exists($routePath)) return;
        $files = scandir($routePath);
        $appInfo = pathinfo($routePath);
        $middleware = $middlewares[$appInfo['filename']] ?? [];
        foreach ($files as $kk => $vv){
            $versionInfo = pathinfo($vv);
            if($versionInfo['extension'] == 'php'){
                App::route()->getRule()->setApp($appInfo['filename'], $versionInfo['filename'], $middleware);
                $routeFile = App::routePath() .DIRECTORY_SEPARATOR.$v.'/'. $vv;
                include $routeFile; // 事先载入路由
                try {
                    $this->updateAllApiDoc($appInfo['filename'], App::route()->getAll($appInfo['filename']));
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
                    $this->output->output('【警告】@apiGroup 分组名称未定义'.$version.'/'.$action, 'info');
                    continue;
                    //App::error()->setError('@apiGroup 分组名称未定义'.$version.'/'.$action);
                }
                foreach($requestTypes as $vv){
                    $apiDocData['param'][$vv] = $this->service->getApiDocParam($version_, $action, strtolower($vv)); // 拿到文档参数
                    $apiDocData['explain'][$vv] = $this->service->getApiDocExplain($version_, $action, strtolower($vv)); // 拿到错误代码解析
                }
                $apiDocData['param'] = json_encode($apiDocData['param'], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
                $apiDocData['explain'] = json_encode($apiDocData['explain'], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
                $this->service->maintain($apiDocData); // 更新维护文档
                $this->output->output(PHP_EOL.'【成功】'.str_replace('_', '.', $version_).$uri, 'success');
            }catch (\Throwable $e){
                echo "【错误】";
                $this->output->exception($e);
            }
            echo PHP_EOL.'======================================================================================'.PHP_EOL;
        }
    }
    // 维护单个文档
    protected function updateApiDoc(){

    }


    public function help(){
        echo 'php cmd.php api:manager delete {admin|index|all} [删除某条api文档]'.PHP_EOL;
        echo 'php cmd.php api:manager clean {admin|index|all} [清空api文档]'.PHP_EOL;
        echo 'php cmd.php api:manager rollback {admin|index|all} [回滚至上一次api文档]'.PHP_EOL;
        echo 'php cmd.php api:manager generate {admin|index|all} [生成api文档]'.PHP_EOL;
    }

    protected function delete(){
        $version = $this->input->input('version', '删除的版本');
        $uri = $this->input->input('uri', '删除的uri');
        $version ?? App::error()->setError('--version 不能为空');
        $uri ?? App::error()->setError('--uri 不能为空');

        $obj = new ApiDocService($this->appType);
        $obj->delete($version, $uri);
        $this->output->output('删除成功', 'success');
    }

    public function rollback(){
        $obj = new ApiDocService($this->appType);
        $obj->rollback();
        $this->output->output('回滚成功', 'success');
    }



    public function clean(){
        $obj = new ApiDocService($this->appType);
        $obj->clean();
        $this->output->output('清空成功', 'success');
    }

    public function __call(string $name, array $arguments){
        $this->output->output("没有".$name."的指令, 指令参考如下:", 'error');
        $this->help();
    }
}
