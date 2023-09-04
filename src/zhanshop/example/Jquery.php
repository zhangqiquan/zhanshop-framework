<?php

namespace zhanshop\example;

class Jquery
{
    protected $param = "";

    protected $apiDoc;

    public function __construct(array $apiDoc)
    {
        $this->apiDoc = $apiDoc;
    }


    protected function param(array &$param, int $tn = 1){
        $tstr = str_repeat("    ", $tn);
        if($this->param == false || $this->param[mb_strlen($this->param) - 1] == ':'){
            $this->param .= '{'.PHP_EOL;
        }else{
            $this->param .= $tstr.'{'.PHP_EOL;
        }

        foreach($param as $v){
            if($v['type'] == 'object' && $v['children']){
                $this->param .= $tstr.'   "'.$v['name'].'":';
                $this->param .= $this->param($v['children'], $tn + 1);
            }elseif($v['type'] == 'array'){
                $this->param .= PHP_EOL.$tstr.'   "'.$v['name'].'":['.PHP_EOL;
                $this->param .= $this->param($v['children'], $tn + 1);
                $this->param .= ']';
            }else{
                if($v['type'] == 'string'){
                    $this->param .= $tstr.'   "'.$v['name'].'":"'.$v['default'].'", //'.$v['description'].PHP_EOL;
                }elseif($v['type'] == 'file'){
                    $this->param .= $tstr.'   "'.$v['name'].'":{}, //'.$v['description'].PHP_EOL;
                }else{
                    if(is_string($v['default'])){
                        $v['default'] = '"'.$v['default'].'"';
                    }else{
                        $v['default'] = 0;
                    }
                    $this->param .= $tstr.'   "'.$v['name'].'":'.$v['default'].', //'.$v['description'].PHP_EOL;
                }

            }

        }
        $this->param .= PHP_EOL.$tstr.'}';
    }

    public function headerCode($header){
        $this->param = "";

        $this->param($header);
        $code = $this->param;

        $this->param = "";
        return $code;
    }

    public function paramCode($param){
        $this->param = "";

        $this->param($param);
        $code = $this->param;

        $this->param = "";
        return $code;
    }

    public function getCode(string $url, string $method, array $header){
        foreach($this->apiDoc['apiHeader'] as $k => $v){
            if(isset($header[$v['name']])){
                $this->apiDoc['apiHeader'][$k]['default'] = $header[$v['name']];
            }
        }
        $header =  $this->headerCode($this->apiDoc['apiHeader']);
        $data = $this->paramCode($this->apiDoc['apiParam']);

        $code = "// jquery示例代码 //
var request = $.ajax({
    //请求的接口地址
    url: '".$url."',
    //请求方法
    method: '".strtoupper($method)."',
     //超时时间设置，单位毫秒
    timeout: 30000,
    //请求头参数
    headers: ".$header.",
    //请求的数据
    data: ".$data.",
});
// 请求成功
request.done(function(data) {
    alert(JSON.stringify(data));
});
// 请求异常
request.fail(function(jqXHR) {
    alert(\"接口出错\\n\"+jqXHR.statusText);
});".PHP_EOL;
        return $code;
    }
}