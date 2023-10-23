<?php

namespace zhanshop\service\git;

use app\task\GieeTask;
use zhanshop\App;
use zhanshop\Error;
use zhanshop\Log;
use zhanshop\Request;
use zhanshop\Response;

class Gitee
{
    protected  $pullBranch = 'master';
    /**
     * 获取事件
     * @param string $hookName
     * @param bool $create
     * @param bool $delete
     * @return string
     */
    protected function getEvent(string $hookName, bool $create, bool $delete){
        if($hookName == 'push_hooks'){
            return 'push';
        }
        return 'other';
    }

    public function verify(string $auth){
        $gitKey = App::env()->get('GIT_KEY', 'zhangqiquan');
        if($auth != $gitKey){
            App::error()->setError("认证失败", Error::FORBIDDEN);
        }
    }

    /**
     * 分支
     * @param string $ref
     * @return string
     */
    protected function gitBranch(string $ref){
        $arr = explode('/', $ref);
        $branch = $arr[count($arr) - 1];
        return $branch;
    }

    public function handle($arr){
        $event = $this->getEvent($arr['hook_name'] ?? '', (bool)($arr['created'] ?? false), (bool)($arr['deleted'] ?? false));
        if($this->pullBranch == $this->gitBranch($arr['ref'] ?? '')){
            if($event == 'push'){
                $gitUrl = $arr['repository']['clone_url'] ?? '';
                if($gitUrl){
                    App::task()->callback([
                        GieeTask::class,
                        'pull'
                    ], $gitUrl, $this->pullBranch);
                }
                Log::errorLog(SWOOLE_LOG_NOTICE, $gitUrl.' ' . $this->pullBranch.'分支推送了代码');
            }
        }
    }

    /**
     * 更新代码
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function push(Request &$request, Response &$response){
        $arr = $data ?? [];
        //$this->verify();
        error_log(print_r($request->param(), true), 3, App::runtimePath().'/gitee.log');
    }
}