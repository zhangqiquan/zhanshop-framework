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
        $this->appName = $all[1] ?? 'index';
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
                $routeFile = App::routePath() .DIRECTORY_SEPARATOR.$this->appName. DIRECTORY_SEPARATOR. $vv;
                include $routeFile; // 事先载入路由
                try {
                    $this->updateAllApiDoc(str_replace('.php', '', $vv), App::route()->getAll()[$this->appName]);
                }catch (\Throwable $e){
                    continue;
                }
                App::route()->clean(); // 清理掉
            }
        }
    }

    protected $runNun = 1;

    protected function updateAllApiDoc(string $version, array $routes){
        $version_ = str_replace('.', '_' , $version);
        $data = $routes[$version];
        foreach($data as $k => $v){
            //echo '###'.$this->runNun++;
            $apiDocData = [];
            $handler = $v['handler'];
            $uri = $k;
            try {
                $apiDocData['version'] = $version;
                $apiDocData['uri'] = $uri;
                $apiDocData['handler'] = json_encode($handler, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
                $apiDocData['method'] = json_encode($v['methods'], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
                $apiDocData['title'] = $this->service->getApiDocTitle($handler); // 拿到apiDoc标题
                $apiDocData['groupname'] = $this->service->getApiDocGroup($handler); // 拿到apiDoc分组
                // 去除所有空格
                if($apiDocData['groupname'] == false){
                    $this->output->output('【警告】@apiGroup 分组名称未定义'.implode(':', $handler), 'info');
                    continue;
                    //App::error()->setError('@apiGroup 分组名称未定义'.$version.'/'.$action);
                }
                foreach($v['methods'] as $vv){
                    $apiDocData['description'][$vv] = $this->service->getApiDoDesc($v['service'], strtolower($vv).$v['service'][1]); // 拿到apiDoc标题
                    // 获取service
                    $apiDocData['header'][$vv] = $this->service->getApiDocHeader($v['service'], strtolower($vv).$v['service'][1]); // 拿到文档参数
                    $apiDocData['param'][$vv] = $this->service->getApiDocParam($v['service'], strtolower($vv).$v['service'][1]); // 拿到文档参数
                    $apiDocData['response'][$vv] = null;
                    $apiDocData['explain'][$vv] = $this->service->getApiDocExplain($v['service'], strtolower($vv).$v['service'][1]); // 拿到错误代码解析
                    $apiDocData['detail'][$vv] = null; // 拿到apiDoc标题
                }
                $apiDocData['header'] = json_encode($apiDocData['header'], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
                $apiDocData['param'] = json_encode($apiDocData['param'], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
                $apiDocData['response'] = json_encode($apiDocData['response'], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
                $apiDocData['explain'] = json_encode($apiDocData['explain'], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
                $apiDocData['detail'] = json_encode($apiDocData['detail'], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
                $apiDocData['description'] = json_encode($apiDocData['description'], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
                //print_r($apiDocData);die;
                $this->service->maintain($apiDocData); // 更新维护文档
                //$this->output->output(PHP_EOL.'【成功】'.str_replace('_', '.', $version_).$uri, 'success');
            }catch (\Throwable $e){
                echo "【错误】";
                $this->output->exception($e);
            }
            //echo PHP_EOL.'OK'.PHP_EOL;
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
