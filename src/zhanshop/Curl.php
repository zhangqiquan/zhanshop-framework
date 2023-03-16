<?php
// +----------------------------------------------------------------------
// | flow-course / Curl.php    [ 2022/2/28 10:02 AM ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2022 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop;

class Curl
{
    protected $config = [
        'cookie' => '',
        'timeout' => 5000,
        'header' => [],
        'upload' => [], // 上传对象
        'useragent' => 'Mozilla/5.0 (Windows NT 11.0; Win64; x64; rv:96.0) Gecko/20100101 Firefox/96.0',
        'ipresolve' => 1,
        'referer' => '', // 请求来源
        'maxredirs' => 3, // 最大跳转次数
    ];

    /**
     * 设置curl请求超时时间
     * @param int $timeout
     * @return $this
     */
    public function setTimeout(int $timeout = 3000){
        $this->config['timeout'] = $timeout;
        return $this;
    }

    /**
     * 设置请求头参数
     * @param string $key
     * @param string $val
     * @return $this
     */
    public function setHeader(string $key, string $val){
        $this->config['header'][] = $key.':'.$val;
        return $this;
    }

    /**
     * 设置curl请求cookie
     * @param string $key
     * @param string $val
     * @return $this
     */
    public function setCookie(string $key, string $val){
        $this->config['cookie'] .= $key.'='.$val.';';
        return $this;
    }

    /**
     * 设置上次文件
     * @param string $key
     * @param string $filePath
     * @return $this
     */
    public function setUpload(string $key, string $filePath){
        $curlFile = new \CURLFile(realpath($filePath));
        $curlFile->setPostFilename(basename($filePath));
        $this->config['upload'][$key] = $curlFile;
        return $this;
    }

    /**
     * 设置浏览器UserAgent信息
     * @param string $useragent
     * @return $this
     */
    public function setUseragent(string $useragent){
        $this->config['useragent'] = $useragent;
        return $this;
    }

    /**
     * 解析方式仅支持【1:ipv4|2:ipv6】
     * @param int $ipresolve
     * @return $this
     */
    public function setIpresolve(int $ipresolve){
        $this->config['ipresolve'] = $ipresolve;
        return $this;
    }

    /**
     * 伪造http访问来源地址
     * @param string $url
     * @return $this
     */
    public function setReferer(string $url){
        $this->config['referer'] = $url;
        return $this;
    }

    /**
     * 设置请求中最大跳转次数
     * @param int $num
     * @return $this
     */
    public function setMaxredirs(int $num){
        $this->config['maxredirs'] = $num;
        return $this;
    }

    /**
     * 处理post请求参数
     * @param $data
     * @param $existingKeys
     * @param $returnArray
     * @return array
     */
    protected function buildPostFields($data, $existingKeys = '', &$returnArray = []){
        if(($data instanceof CURLFile) or !(is_array($data) or is_object($data))){
            $returnArray[$existingKeys]=$data;
            return $returnArray;
        }
        else{
            foreach ($data as $key => $item) {
                $this->buildPostFields($item,$existingKeys?$existingKeys."[$key]":$key,$returnArray);
            }
            return $returnArray;
        }
    }

    /**
     * 请求
     * @param string $url
     * @param string $method
     * @param array $data
     * @return bool|string
     */
    public function request(string $url, string $method = 'GET', string|array $data = [], $contentType = '', bool $report = false){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); //设置请求方式
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);

        if($data){
            curl_setopt($ch, CURLOPT_POST, 1);
            if(is_array($data)){
                $data = json_encode($data, JSON_UNESCAPED_UNICODE);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }elseif($this->config['upload']){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->config['upload']);
        }

        if($contentType) $this->config['header'][] = 'Content-Type'.':'.$contentType;

        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 跟踪爬取重定向页面302 redirect
        curl_setopt($ch, CURLOPT_MAXREDIRS, $this->config['maxredirs']);//最多重定向3次
        curl_setopt($ch, CURLOPT_USERAGENT, $this->config['useragent']);
        curl_setopt($ch, CURLOPT_IPRESOLVE, $this->config['ipresolve']);//在对应域名没有IPv6的情况下，会等待IPv6 DNS解析失败 TIMEOUT 之后才按以前的正常流程去找IPv4，
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->config['timeout']);
        curl_setopt($ch, CURLOPT_COOKIE, $this->config['cookie']);
        curl_setopt($ch, CURLOPT_REFERER, $this->config['referer']);
        if($this->config['header']){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->config['header']);//设置请求头
        }
        $output = curl_exec($ch);
        $curlInfo = curl_getinfo($ch);
        $httpCode = $curlInfo['http_code'];
        // 如果发生错误打印错误报告
        if($httpCode != 200) App::error()->setError($url.'请求'.$httpCode.'错误'.',请求:'. "".',响应:'.$output, 500);
        //curl_close($ch);
        $outData = [
            'code' => $httpCode,
            'body' => $output,
            'time' => $curlInfo['total_time'],
        ];
        $this->config['cookie'] = '';
        $this->config['header'] = [];
        $this->config['upload'] = [];
        if($report) $outData['report'] = $curlInfo;

        return $outData;
    }
}