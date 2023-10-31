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

    protected  $pullAuth = 'zhangqiquan';

    public function __construct()
    {
        $pullAuth = App::env()->get('GIT_KEY', '');
        if($pullAuth) $this->pullAuth = $pullAuth;

        $pullBranch = App::env()->get('GIT_BRANCH', '');
        if($pullBranch) $this->pullBranch = $pullBranch;
    }

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
        }else if($hookName == 'tag_push_hooks'){
            return 'tag';
        }
        return 'other';
    }

    public function verify(string $auth){

        if($auth != $this->pullAuth){
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
        $auth = $request->get("auth");
        $this->verify($auth);
        $this->handle($request->post());
    }
}