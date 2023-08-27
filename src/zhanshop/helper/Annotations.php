<?php

namespace zhanshop\helper;
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
        $matched = preg_match('/@api\s*(.*)/i', $this->docComment, $matches);
        var_dump($matches);
    }

    public function apiGroup(){
        $matched = preg_match('/@apiGroup\s*(.*)/i', $this->docComment, $matches);
        var_dump($matches);
    }

    public function apiMiddleware(){
        $matched = preg_match('/@apiMiddleware\s*(.*)/i', $this->docComment, $matches);
        var_dump($matches);
    }

    public function apiHeader(){
        $matched = preg_match('/@apiHeader\s*(.*)/i', $this->docComment, $matches);
        var_dump($matches);
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
        $this->api();
        $this->apiGroup();
        $this->apiMiddleware();
        $this->apiHeader();
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