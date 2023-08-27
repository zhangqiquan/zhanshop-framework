<?php

namespace zhanshop\service;
/**
 * 注解支持123
 * @api {method} path title
 * @apiGroup User
 * @apiHeader {String} 字段名 描述
 * @apiParam {Int} page=1 页码
 * @apiMiddleware 1,2
 * @apiDescription text
 */
class Annotations
{
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