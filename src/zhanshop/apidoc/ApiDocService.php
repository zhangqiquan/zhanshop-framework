<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / ApiDocService.php    [ 2023/8/17 下午8:42 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\apidoc;

use zhanshop\App;

class ApiDocService
{
    protected $app;
    public function __construct(string $app)
    {
        $this->app = $app;
    }

    public function menu(){
        $list = App::database()->model("apidoc")->where(['app' => $this->app])->field('*')->field('id,title,catname,app,app,protocol,version,uri')->order('id asc')->select();
        print_r($list);
    }
}