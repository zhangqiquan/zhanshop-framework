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
    /**
     * 所有api菜单
     * @param string $app
     * @return array
     */
    public function menu(string $app){
        $list = App::database()->model("apidoc")->where(['app' => $app])->field('id,title,catname,protocol,app,version,uri')->order('id asc')->select();
        $menus = [];

        $groupNames = array_unique(array_column($list, 'catname'));

        foreach($groupNames as $k => $v){
            $menus[$k]['title'] = $v;
            $menus[$k]['apis'] = [];
            foreach($list as $vv){
                if($vv['catname'] == $v){
                    if(!isset($menus[$k]['apis'][$vv['protocol'].'/'.$vv['uri']])){
                        $menus[$k]['apis'][$vv['protocol'].'/'.$vv['uri']] = [
                            'protocol' => $vv['protocol'],
                            'uri' => $vv['uri'],
                            'title' => $vv['title'],
                        ];
                    }
                }
            }
            $menus[$k]['apis'] = array_values($menus[$k]['apis']);
        }
        return $menus;
    }

    public function detail(string $app, string $protocol, string $uri, string $version = '', string $method = ""){
        $where = ['app' => $app, 'protocol' => $protocol, 'uri' => $uri];
        if($method) $where['method'] = $method;

        if($version == false){
            $version = App::database()->model("apidoc")->where($where)->order('version desc')->order('id asc')->value('version');
            $where['version'] = $version;
        }else{
            $where['version'] = $version;
        }

        $listData = App::database()->model("apidoc")->where($where)->order('id asc')->selectOrFail();

        $data = [];
        foreach($listData as $v){
            $v['header'] = json_decode($v['header'] ?? '[]', true);
            if($v['header']){
                $header = [];
                foreach($v['header'] as $field => $head){
                    $header[] = ['name' => $head['name'], 'type' => 'string', 'default' => '', 'example' => '', 'description' => $head['description']];
                }
                $v['header'] = $header;
            }

            $v['param'] = json_decode($v['param'] ?? '[]', true);

            $v['response'] = json_decode($v['response'] ?? '[]', true);
            $v['success'] = json_decode($v['success'] ?? '[]', true);
            $v['failure'] = json_decode($v['failure'] ?? '[]', true);
            $v['explain'] = json_decode($v['explain'] ?? '[]', true);
            $data[] = $v;
        }
        unset($where['version']);
        $versions = App::database()->model("apidoc")->where($where)->field('version')->order('version desc')->select();
        return [
            'detail' => $data,
            'versions' => array_column($versions, 'version'),
        ];
    }


    public function success(int $id, string $success){
        App::database()->model("apidoc")->where(['id' => $id])->update(['success' => $success]);
    }

    public function failure(int $id, string $success){
        App::database()->model("apidoc")->where(['id' => $id])->update(['failure' => $success]);
    }

    /**
     * 获取控制器入参验证代码
     * @param $class
     * @param $method
     * @return array
     * @throws \ReflectionException
     */
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
                    $required = 'false';
                    if(strpos($verify, 'int') !== false) $type = 'int';
                    if(strpos($verify, 'array') !== false) $type = 'array';
                    if(strpos($verify, 'object') !== false) $type = 'object';
                    if(strpos($verify, 'required') !== false) $required = 'true';
                    $params[$field] = [
                        'name' => $field,
                        'type' => $type,
                        'default' => '',
                        'required' => $required,
                        'title' => '',
                        'description' => ''
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
                        $params[$arr[0]]['title'] = $arr[1];
                    }
                }
            }
        }

        return array_values($params);
    }
}