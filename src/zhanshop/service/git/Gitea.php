<?php

namespace zhanshop\service\git;

use app\task\GiteaTask;
use zhanshop\App;

class Gitea
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

    protected function gitBranch(string $ref){
        $arr = explode('/', $ref);
        $branch = $arr[count($arr) - 1];
        return $branch;
    }

    public function handle($arr){
        if($this->pullBranch == $this->gitBranch($arr['ref'] ?? '')){
            App::task()->callback([
                GiteaTask::class,
                'pull'
            ], $gitUrl, $this->pullBranch);
        }
    }
}