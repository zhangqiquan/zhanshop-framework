<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / ApiDocService.php    [ 2023/8/18 14:54 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\service;

use zhanshop\App;

class ApiDocService
{
    public function menu(string $app){
        $list = App::database()->model("apidoc")->where(['app' => $app])->field('id,title,catname,protocol,app,version,uri')->order('id asc')->select();
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