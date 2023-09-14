<?php

namespace zhanshop;

class Document
{
    const selfCloseLabel = ['br', 'hr', 'area', 'base', 'img', 'input', 'link', 'meta', 'param', 'col', 'colgroup', 'command', 'frame', 'embed', 'keygen', 'source'];

    protected $outHTML = [];

    public function __construct(string $outHTML)
    {
        $this->outHTML = [$outHTML];
    }

    public function getElementById(){

    }

    public function getElementsByName(){

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