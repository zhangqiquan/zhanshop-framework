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
use zhanshop\helper\FileSystem;

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


    public function apiRequestParamCode($class, $method){
        // 使用反射拿代码
        $rc = new \ReflectionClass($class);
        $method = $rc->getMethod($method);
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $code = FileSystem::extract($method->getFileName(), $startLine + 1, $endLine);
        $validate = explode(')->getData()', $code)[0];
        $ruleCode = explode('[', str_replace([' ', '"', "'", ","], '', $validate));

        $params = [];
        if(isset($ruleCode[1])){
            $rules = explode("\n", $ruleCode[1]);
            foreach($rules as $k => $v){
                $fields = explode('=>', $v);
                if(count($fields) == 2){
                    $field = $fields[0];
                    $verify = $fields[1];
                    $type = 'string';
                    if(strpos($verify, 'int') !== false) $type = 'int';
                    if(strpos($verify, 'array') !== false) $type = 'array';
                    if(strpos($verify, 'object') !== false) $type = 'object';
                    $params[$field] = [
                        'name' => $field,
                        'type' => $type,
                        'default' => '',
                        'example' => '',
                        'description' => '',
                    ];
                }
            }
        }

        if(isset($ruleCode[2])){
            $rules = explode("\n", $ruleCode[2]);
            foreach($rules as $k => $v){
                $arr = explode('=>', $v);
                if(count($arr) == 2){
                    if(isset($params[$arr[0]])){
                        $params[$arr[0]]['description'] = $arr[1];
                    }
                }
            }
        }

        return array_values($params);
    }
}