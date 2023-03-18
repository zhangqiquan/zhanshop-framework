<?php
// +----------------------------------------------------------------------
// | zhanshop-framework / Chuanglan.php    [ 2023/3/18 21:38 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: Administrator <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\sns\sms\drive;

use zhanshop\App;

class Chuanglan
{
    protected $sendUrl = 'http://smssh1.253.com/msg/variable/json';
    protected $balanceUrl = 'http://smssh1.253.com/msg/balance/json';

    protected $config = [
        // 账号
        'account' => 'xxx',
        // 密码
        'password' => 'xxx',
        // 发送的消息
        'msg' => '【253云通讯】您好，您的验证码是{$var},请在{$var}分钟内完成注册，感谢您的使用。',
        // 是否显示报告
        'report' => true,
    ];

    public function __construct(){
        $config = App::config()->get('sns.chuanglan'); // sns.chuanglan.sms
        $this->config = array_merge($this->config, $config['sms'] ?? []);
    }

    public function setMsg(string $msg){
        $this->config['send_msg'] = $msg;
    }

    public function balance(){
        $data = App::curl()->request($this->balanceUrl, 'POST', ['account' => $this->config['account'], 'password' => $this->config['password']], 'application/json');
        if($data['code'] != 200 || $data['body'] == false) App::error()->setError('创蓝余额接口http错误：'.$data['body'], 500);
        $body = json_decode($data['body'], true);
        if(isset($body['code']) && $body['code'] != 0) App::error()->setError('创蓝余额接口响应错误：'.$data['body']);
        return $body;
    }

    protected $params = '';

    /**
     * 设置消息
     * @param string $phone
     * @param mixed ...$param
     * @return void
     */
    public function addressee(string $phone, mixed ...$param){
        $this->params .= $phone.','.implode(',', $param);
    }

    /**
     * 发送短信
     * @return void
     * @throws \Exception
     */
    public function send(){
        $sendMsg = $this->config;
        $sendMsg['params'] = $this->params;
        $data = App::curl()->request($this->sendUrl, 'POST', $sendMsg, 'application/json; charset=utf-8');
        $this->params = '';
        if($data['code'] != 200 || $data['body'] == false) App::error()->setError('创蓝短信接口发生http错误：'.$data['body'], 500);
        $body = json_decode($data['body'], true);
        if(isset($body['code']) && $body['code'] != 0) App::error()->setError('创蓝短信接口响应错误, request: '.json_encode($sendMsg, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE).' , response:'.$data['body']);
        return $body;
    }
}