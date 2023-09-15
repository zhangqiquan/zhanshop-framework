<?php

namespace zhanshop;
/**
元字符	说明
[a-z]	匹配任何包含小写字母a-z的字符串
[A-Z]	匹配任何包含大写字母A-Z的字符串
[0-9]	匹配任何包含0-9的字符串
[abc]	匹配任何包含小写字母a,b,c的字符串
[^abc]	匹配任何不包含小写字母a,b,c的字符串
[a-zA-Z0-9_]	匹配任何包含a-zA-Z0-9和下划线的字符串
\w	匹配任何包含a-zA-Z0-9和下划线的字符串
\W	匹配任何不包含a-zA-Z0-9和下划线的字符串
\d	匹配任何包含数字字符
\D	匹配任何非数字字符
\s	匹配任何空白字符
\S	匹配任何非空白字符
\b	匹配是否到达了单词边界
\B	匹配没有到达了单词边界
\	匹配正则中的特殊字符
 */
class Document
{
    const selfCloseLabel = ['br', 'hr', 'area', 'base', 'img', 'input', 'link', 'meta', 'param', 'col', 'colgroup', 'command', 'frame', 'embed', 'keygen', 'source'];

    protected $outHTML = [];

    public function __construct(string $outHTML)
    {
        $this->outHTML = [$outHTML];
    }

    /**
     * 获取id
     * @param string $idName
     * @return $this
     */
    public function getElementById(string $idName){
        $allMatches = [];
        foreach($this->outHTML as $html){
            $pattern = '/<([a-zA-Z]+) [^<>]*id="'.$className.'".*>/iUs'; // 加上U之后它只匹配了每个的第一个值
            if(preg_match($pattern, $html, $matches)){
                $label = $matches[1];
                if(in_array($label, self::selfCloseLabel)){
                    $pattern = '/<'.$label.' [^<>]*id=["|\']'.$className.'[^<>]*>(.*)'.'>/iUs';
                    preg_match_all($pattern, $html, $matches);
                    $allMatches = array_merge($allMatches, $matches[0]);
                }else{
                    $pattern = '/<'.$label.' [^<>]*id=["|\']'.$className.'[^<>]*>(.*)<\/'.$label.'>/iUs'; // 没有s会导致匹配失败
                    preg_match_all($pattern, $html, $matches);
                    $allMatches = array_merge($allMatches, $matches[0]);
                }
            }
        }
        $this->outHTML = $allMatches;
        return $this;
    }

    /**
     * 获取name
     * @param string $name
     * @return $this
     */
    public function getElementsByName(string $name){
        $allMatches = [];
        foreach($this->outHTML as $html){
            $pattern = '/<([a-zA-Z]+) [^<>]*name="'.$className.'".*>/iUs'; // 加上U之后它只匹配了每个的第一个值
            if(preg_match($pattern, $html, $matches)){
                $label = $matches[1];
                if(in_array($label, self::selfCloseLabel)){
                    $pattern = '/<'.$label.' [^<>]*name=["|\']'.$className.'[^<>]*>(.*)'.'>/iUs';
                    preg_match_all($pattern, $html, $matches);
                    $allMatches = array_merge($allMatches, $matches[0]);
                }else{
                    $pattern = '/<'.$label.' [^<>]*name=["|\']'.$className.'[^<>]*>(.*)<\/'.$label.'>/iUs'; // 没有s会导致匹配失败
                    preg_match_all($pattern, $html, $matches);
                    $allMatches = array_merge($allMatches, $matches[0]);
                }
            }
        }
        $this->outHTML = $allMatches;
        return $this;
    }

    /**
     * 获取标签数据列表
     * @param string $tag
     * @return $this
     */
    public function getElementsByTagName(string $tag){
        if(in_array($tag, self::selfCloseLabel)){
            $pattern = '/<'.$tag.'.*>/iUs';
        }else{
            $pattern = '/<'.$tag.'.*>(.*)<\/'.$tag.'>/iUs';
        }

        $allMatches = [];
        foreach($this->outHTML as $html){
            preg_match_all($pattern, $html, $matches);
            $allMatches = array_merge($allMatches, $matches[0]);
        }
        $this->outHTML = $allMatches;
        return $this;
    }

    /**
     * 获取class的数据
     * @param $className
     * @return string[]|\string[][]
     */
    /**
     * 获取class的数据
     * @param $className
     * @return $this
     */
    public function getElementsByClassName($className){
        $allMatches = [];
        foreach($this->outHTML as $html){
            $pattern = '/<([a-zA-Z]+) [^<>]*class="'.$className.'".*>/iUs'; // 加上U之后它只匹配了每个的第一个值
            if(preg_match($pattern, $html, $matches)){
                $label = $matches[1];
                if(in_array($label, self::selfCloseLabel)){
                    $pattern = '/<'.$label.' [^<>]*class=["|\']'.$className.'[^<>]*>(.*)'.'>/iUs';
                    preg_match_all($pattern, $html, $matches);
                    $allMatches = array_merge($allMatches, $matches[0]);
                }else{
                    $pattern = '/<'.$label.' [^<>]*class=["|\']'.$className.'[^<>]*>(.*)<\/'.$label.'>/iUs'; // 没有s会导致匹配失败
                    preg_match_all($pattern, $html, $matches);
                    $allMatches = array_merge($allMatches, $matches[0]);
                }
            }
        }
        $this->outHTML = $allMatches;
        return $this;
    }

    /**
     * 获取包含自身的html
     * @return mixed|string
     */
    public function outHtml(){
        if(count($this->outHTML) == 1) return $this->outHTML[0];
        return $this->outHTML;
    }

    /**
     * 获取文本
     * @return string
     */
    public function innerText(){
        $allMatches = [];
        foreach($this->outHTML as $html){
            $allMatches[] = strip_tags($html);
        }
        if(count($allMatches) == 1) return $allMatches[0];
        return $allMatches;
    }

    /**
     * 替换属性
     * @param string $name
     * @param string $val
     * @return void
     */
    public function setAttribute(string $name, string $val){
        $allMatches = [];
        $pattern = '/'.$name.'=[\'|"](.*)[\'|"]/iUs'; // 记得给或加上中括号
        foreach($this->outHTML as $k => $html){
            $this->outHTML[$k] = preg_replace($pattern,$name.'="'.$val.'"', $html);
        }
    }

    /**
     * 获取属性
     * @param string $name
     * @return string|null
     */
    public function getAttribute(string $name){
        $allMatches = [];
        $pattern = '/'.$name.'=[\'|"](.*)[\'|"]/iUs'; // 记得给或加上中括号
        foreach($this->outHTML as $html){
            preg_match($pattern, $html, $matches);
            $allMatches[] = $matches[1] ?? null;
        }
        if(count($allMatches) == 1) return $allMatches[0];
        return $allMatches;
    }

    public function toArray(){
        return $this->outHTML;
    }
}