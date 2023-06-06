<?php

namespace zhanshop\helper;

class HtmlDocument
{
    protected $outerHTML = ''; // 这个是自身
    protected $innerHTML = ''; // 这个是子内容

    public function __construct(string $html, $node = 'body'){
        $this->outerHTML = $html;
        $pattern =  '/<'.$node.'.*?>'.'(.*?)'.'<\/'.$node.'>/is'; // 需要走最少的匹配也就是非贪婪
        preg_match_all($pattern,
            $html,
            $outs);
        $this->innerHTML = $outs[1][0] ?? '';
    }

    /**
     * 搜索并返回结果
     * @param string $html
     * @param $startCode
     * @param string $endCode
     * @param $returnType
     * @return array|string[]|\string[][]
     */
    public static function serach(string &$html, $startCode, string $endCode, $returnType = 1){
        $pattern =  '/'.self::escape($startCode).'(.*?)'.self::escape($endCode).'/is'; // 需要走最少的匹配也就是非贪婪
        preg_match_all($pattern,
            $html,
            $outs);

        if($returnType < 0){
            return $outs;
        }

        return $outs[$returnType] ?? [];
    }
    /**
     * 获取所有的链接
     * @param string $html
     * @return void
     */
    public static function hrefs(string $html){
        $pattern = "/href=['|\"](.*?)['|\"].*?>(.*?)<\/a>/is"; // 括号发挥了作用 是用来分组的
        preg_match_all($pattern,
            $html,
            $outs);

        if(count($outs) == 3){
            $hrefs = [];
            foreach ($outs[1] as $k => $v){
                $hrefs[] = [
                    'href' => $v,
                    'title' => strip_tags($outs[2][$k])
                ];
            }
            return $hrefs;
        }
        return [];
    }

    protected static function escape($str){
        return str_replace(['/', '.', '?', '*', '+'], ['\/', '\.', '\?', '\*', '\+'], $str);
    }

    /**
     * 通过ID获取htmlDOM对象
     * @param string $name
     * @return HtmlDocument
     */
    public function getElementById(string $name, string $endHtml = ''){
        // 暂时只支持到有闭合标签的代码
        if($endHtml == false){
            $endHtml = '<\/(.*?)>';
        }else{
            $endHtml = $this->escape($endHtml);
        }
        $pattern =  '/id=["|\']'.$name.'["|\'].*?>(.*?)'.$endHtml.'/is';
        preg_match($pattern,
            $this->outerHTML,
            $outs);
        if($outs){
            if(isset($outs[2])){
                $outs[0] = '<'.$outs[2].' '.$outs[0];

                return new self($outs[0], $outs[2]);
            }
            return new self($outs[0], $outs[2] ?? 'div');
        }

        return null;
    }

    public function getElementsByClassName(string $name, string $endHtml = ''){
        if($endHtml == false){
            $endHtml = '<\/(.*?)>';
        }
        $pattern =  '/<'.$name.' .*?>(.*?)'.$endHtml.'/is';
        var_dump($pattern);
        preg_match($pattern,
            $this->outerHTML,
            $outs);

        if($outs){
            if(isset($outs[2])){
                $outs[0] = '<'.$outs[2].' '.$outs[0];

                return new self($outs[0], $outs[2]);
            }
            return new self($outs[0], $outs[2] ?? 'div');
        }

        return null;
    }

    public function getElementsByTagName(string $name, string $endHtml = ''){
        if($endHtml == false){
            $endHtml = '<\/(.*?)>';
        }
        $pattern =  '/<'.$name.'.*?>(.*?)'.$endHtml.'/is';
        preg_match($pattern,
            $this->outerHTML,
            $outs);
    }

    public function title(){

    }

    /**
     * 获取自身数据
     * @return void
     */
    public function outerHTML(){
        return $this->outerHTML;
    }

    /**
     * 获取自身数据不包含标签
     * @return void
     */
    public function textContent(){
        return strip_tags($this->outerHTML);
    }

    /**
     * 获取子内容不包含标签
     * @param string $name
     * @return void
     */
    public function innerText(string $name){
        return strip_tags($this->innerHTML);
    }

    /**
     * 获取子内容
     * @param string $name
     * @return void
     */
    public function innerHTML(string $name){
        return $this->innerHTML;
    }

    /**
     * 获取属性
     * @param string $name
     * @return void
     */
    public function attribute(string $name){
        $pattern =  '/'.$name.'=["|\'](.*?)'.'["|\']/is'; // 需要走最少的匹配也就是非贪婪
        preg_match($pattern,
            $this->outerHTML,
            $outs);
        return $outs[1] ?? null;
    }

    /**
     * 获取区段代码
     * @param string $html 原始html
     * @param $startCode 起始代码片段
     * @param string $endCode 结束代码片段
     * @param $returnType 返回类型 -1 全部返回包含自身和子内容， 0 返回匹配包含自身的内容 1 仅返回子内容
     * @return array
     */
    protected function getSectionCode(string &$html, $startCode, string $endCode, $returnType = 1){
        $pattern =  '/'.self::escape($startCode).'(.*?)'.self::escape($endCode).'/is'; // 需要走最少的匹配也就是非贪婪
        preg_match_all($pattern,
            $html,
            $outs);

        if($returnType < 0){
            return $outs;
        }

        return $outs[$returnType] ?? [];
    }

    /**
     * 获取所有的连接地址
     * @param string $html
     * @return array
     */
    public static function getHref(string &$html){
        $pattern = "/href=['|\"](.*?)['|\"]>(.*?)<\/a>/is"; // 括号发挥了作用 是用来分组的
        preg_match_all($pattern,
            $html,
            $outs); // 返回包含文字和

        $ret = [];
        foreach($outs[1] as $k => $v){
            $ret[] = [
                'href' => $v,
                'title' => strip_tags($outs[2][$k] ?? '')
            ];
        }
        return $ret;
    }
}