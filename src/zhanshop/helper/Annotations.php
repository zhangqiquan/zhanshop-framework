<?php

namespace zhanshop\service;

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
        $matched = preg_match('/@'.$name.'(\s*.*)/i', $note, $matches);
        var_dump($matches);
    }


}