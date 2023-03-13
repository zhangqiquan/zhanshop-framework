<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / Request.php [ 2023/2/23 下午7:59 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop;

class Request
{
    protected $request;

    protected $method;

    protected $post = [];

    protected $service;

    protected $action;

    protected $ext = [];

    public function __construct(mixed &$request){
        $this->request = $request;

        $inputData = $this->getInputData($request->getContent(), $request->server['request_method'] ?? 'POST');
        $this->post = $inputData ? $inputData : $request->post;
    }

    /**
     * 当前请求参数
     * @var array
     */
    protected $param = []; // 包含get和post

//    public function init(mixed &$request){
//        $this->method = null;
//        $this->request = null;
//        $this->ext = [];
//        $this->get = [];
//        $this->post = [];
//        $this->action = null;
//        $this->ext = [];
//        $this->request = $request;
//
//        $inputData = $this->getInputData($request->getContent());
//
//        $this->post    = $request->post ?: $inputData;
//    }

    public function setAction(string $action){
        $this->action = $action;
    }

    public function getAction(){
        return $this->action;
    }

    public function setService(string $class){
        $this->service = $class;
    }

    public function getService(){
        return App::service()->get($this->service);
    }

    protected function getInputData(string $content, string $method = 'POST'): array
    {
        $contentType = $this->contentType();
        if ($method == 'POST') {
            if(false !== strpos($contentType, 'json')){
                $json = json_decode($content, true);
                if($json == false) App::error()->setError('请求数据不是一个有效的json数据', 403);
                return (array) $json;
            }
        }

        return [];
    }

    /**
     * 设置或者获取当前的Header
     * @access public
     * @param  string $name header名称
     * @param  string $default 默认值
     * @return string|array
     */
    public function header(string $name = '', mixed $default = null)
    {
        if ('' === $name) {
            return $this->request->header;
        }

        $name = str_replace('_', '-', strtolower($name));

        return $this->request->header[$name] ?? $default;
    }

    public function server(string $name = '', mixed $default = null){
        return $this->request->server[$name] ?? $default;
    }

    public function time(bool $flot = false){
        if($flot) return $this->request->server['request_time_float'];
        return $this->request->server['request_time'];
    }

    /**
     * 当前请求 HTTP_CONTENT_TYPE
     * @access public
     * @return string
     */
    public function contentType(): string
    {
        $contentType = $this->header('content-type');

        if ($contentType) {
            if (strpos($contentType, ';')) {
                [$type] = explode(';', $contentType);
            } else {
                $type = $contentType;
            }
            return trim($type);
        }

        return '';
    }

    public function method(bool $original = false){
        if($original) return $this->request->server['request_method'];
        if($this->method) return $this->method;
        $method = $this->post('_method');
        $method = $method ?? $this->request->server['request_method'];
        $this->method = strtolower($method);
        unset($this->post['_method']);
        return $this->method;
    }

    public function get(string $key = '', mixed $default = null){
        if($key == '') return $this->request->get ?? [];
        return $this->request->get[$key] ?? $default;
    }

    public function post(string $key = '', mixed $default = null){
        if($this->post == false) $this->post = array_merge($this->request->post ?? [], $this->request->files ?? []);
        if($key == '') return $this->post ?? [];
        return $this->post[$key] ?? $default;
    }

    public function files(string $key = '', mixed $default = null){
        if($key == '') return $this->request->files ?? [];
        return $this->request->files[$key] ?? $default;
    }

    public function param(string $key = '', mixed $default = null){
        $ret = null;
        switch ($this->request->server['request_method'] ?? 'GET'){
            case "POST":
                $ret = $this->post($key, $default);
                break;
            default:
                $ret = $this->get($key, $default);
                break;
        }
        return $ret;
    }

    public function setExt(string $key, mixed $val){
        $this->ext[$key] = $val;
    }

    public function getExt(string $key, mixed $default = null){
        return $this->ext[$key] ?? $default;
    }
}