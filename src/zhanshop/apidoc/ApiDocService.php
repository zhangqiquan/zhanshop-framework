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
        // 开启了ONLY_FULL_GROUP_BY的设置，如果select 的字段不在 group by 中 报非法 就是group得比 field多
        $list = App::database()->model("apidoc")->where(['app' => $this->app])->field('id,title,catname,protocol,app,version,uri')->order('id asc')->select();

        $menus = [];

        $groupNames = array_unique(array_column($list, 'catname'));

        foreach($groupNames as $k => $v){
            $menus[$k]['title'] = $v;
            $menus[$k]['apis'] = [];
            foreach($list as $vv){
                if($vv['catname'] == $v){
                    $menus[$k]['apis'][$vv['protocol'].'/'.$vv['uri']] = [
                        'protocol' => $vv['protocol'],
                        'uri' => $vv['uri'],
                        'title' => $vv['title'],
                    ];
                }
            }
            $menus[$k]['apis'] = array_values($menus[$k]['apis']);
        }
        return $menus;
    }
}