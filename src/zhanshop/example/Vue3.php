<?php

namespace zhanshop\example;

class Vue3
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
        $headers =  $this->headerCode($this->apiDoc['apiHeader']);
        $params = '{}';
        $data = '{}';
        if($method == 'POST'){
            $data = $this->paramCode($this->apiDoc['apiParam']);
        }else{
            $params = $this->paramCode($this->apiDoc['apiParam']);
        }

        $code = "// vuejs示例代码 //
axios.request({
    // 请求的接口地址
    url: \"".$url."\",
    //请求方法
    method: \"".strtoupper($method)."\",
    //超时时间设置，单位毫秒
    timeout: 30000,
    //请求头参数
    headers: ".$headers.",
    // 与请求一起发送的 URL 参数
    params: ".$params.",
    // 请求主体被发送的数据
    data: ".$data.",
}).then(res => {
    // 请求成功处理
    alert(JSON.stringify(res));
}).catch(function (error) {
    // 请求失败处理
    alert(error.message);
});".PHP_EOL;
        return $code;
    }
}