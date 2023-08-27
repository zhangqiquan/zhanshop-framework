<?php

namespace zhanshop\helper;
use zhanshop\App;

/**
 * 注解支持123
 * @api {method} path title
 * @apiGroup User
 * @apiHeader {String} 字段名 描述
 * @apiParam {Int} page=1 页码
 * @apiSuccess string 字段名 描述
 * @apiError CODE 错误说明
 * @apiMiddleware 1,2
 * @apiDescription text
 */
class Annotations
{
    protected $docComment;
    public function __construct(string $docComment)
    {
        $this->docComment = $docComment;
    }

    public function api(){
        // @api POST goods 添加商品
        // @api  \s+匹配一个或者多个空格  (GET|POST|DELETE|PUT)匹配GET|POST|DELETE|PUT任意一项     \s+匹配一个或者多个空格     (\w+)匹配一个包含字母数字下划线的字符串     \s+匹配一个或者多个空格  (.*)匹配任意字符
        $matched = preg_match('/@api\s+(GET|POST|DELETE|PUT)\s+(\w+)\s+(\S*)/i', $this->docComment, $matches);
        return [
            'method' => $matches[1] ?? '',
            'uri' => $matches[2] ?? '',
            'title' => str_replace(' ', '', $matches[3] ?? ''),
        ];
    }

    public function apiGroup(){
        // @apiGroup 医生
        // @apiGroup \s+ 匹配一个或者多个空格 (\S*) 匹配任意非空白字符
        $matched = preg_match('/@apiGroup\s+(\S*)/i', $this->docComment, $matches);
        return str_replace(' ', '', $matches[1] ?? '');
    }

    public function apiMiddleware(){
        $matched = preg_match('/@apiMiddleware\s+(\S*)/i', $this->docComment, $matches);
        return $matches[1] ?? '';
    }

    protected function moreParam(array &$param, $data, $id){
        foreach($data as $k => $v){
            if($v['pname'] == $id){
                unset($v['pname']);
                $param[$v['name']] = $v;
                $this->moreParam($param[$v['name']]['children'], $data, $v['name']);
            }
        }
    }

    public function apiHeader(){
        //@apiHeader string token 用户token
        $matched = preg_match_all('/@apiHeader\s+([a-zA-Z]+)\s+(\S+)\s+(\S*)/i', $this->docComment, $matches);
        $data = [];
        foreach($matches[2] as $k => $v){
            $fieldsDefault = explode('=', $v);
            $fields = $fieldsDefault[0];
            $default = $fieldsDefault[1] ?? null;

            $fields = explode('.', $fields);
            $pid = $fields[count($fields) - 2] ?? null;
            $field = $fields[count($fields) - 1];
            $data[] = [
                'name' => $field,
                'pname' => $pid,
                'type' => $matches[1][$k],
                'default' => $default,
                'description' => $matches[3][$k],
                'children' => [],
            ];
        }
        $param = [];
        // 进行分组
        foreach($data as $k => $v){
            if($v['pname'] === null){
                unset($v['pname']);
                $param[$v['name']] = $v;
                $this->moreParam($param[$v['name']]['children'], $data, $v['name']);
            }
        }
        print_r($param);die;
    }

    public function apiParam(){
        $matched = preg_match_all('/@apiParam\s*(.*)/i', $this->docComment, $matches);
        var_dump($matches);
    }

    public function apiSuccess(){
        $matched = preg_match_all('/@apiSuccess\s*(.*)/i', $this->docComment, $matches);
        var_dump($matches);
    }

    public function apiError(){
        $matched = preg_match_all('/@apiError\s*(.*)/i', $this->docComment, $matches);
        var_dump($matches);
    }

    public function all(){
        $data = [];
        $data['api'] = $this->api();
        $data['apiGroup'] = $this->apiGroup();
        $data['apiMiddleware'] = array_values(array_filter(explode(',', $this->apiMiddleware())));
        $data['apiHeader'] = $this->apiHeader();
        die;
        $this->apiParam();
        $this->apiSuccess();
        $this->apiError();
    }
    public static function getTitle(string $note){
        $note = explode("\n", $note)[1];
        $matched = preg_match('/\* (.*)/i', $note, $matches);
        var_dump($matches);
    }

    public static function getParam(string $name, string $note){
        // @param Request $request 可能会有空格
        // @ApiGroup 医生 // 可能会有空格
        // @Route(articleList/{id}, GET) 可能没有空格
        // \s* 零个或多个空白字符
        // ^\s 以空白字符开头
        //()当做一个整体 对空号内匹配到的数据单独放一个下标
        $matched = preg_match('/@'.$name.'(\s*.*)/i', $note, $matches);
        var_dump($matches);
    }


}