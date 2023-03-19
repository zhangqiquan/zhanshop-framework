<?php
// +----------------------------------------------------------------------
// | zhanshop-framework / Aliyun.php    [ 2023/3/18 20:55 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: Administrator <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\sns\sms\drive;

use zhanshop\App;

class Aliyun
{
    protected $sendUrl = 'http://dysmsapi.aliyuncs.com';
    /**
     * @var array
     */
    protected $config = [
        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        'access_key_id' => 'xxx',
        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        'access_key_secret' => 'xxx',
        // 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        'sign_name' => '阿里云短信测试',
        // 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        'template_code' => "SMS_154950909",
        // 模版消息
        'template_msg' => '您正在使用阿里云短信测试服务，体验验证码是：${code}，如非本人操作，请忽略本短信！',
        // 短信接口配置信息
        "region_id" => "cn-hangzhou",
        "action" => "SendBatchSms",
        "version" => "2017-05-25",
    ];

    public function __construct(){
        $config = App::config()->get('sns.aliyun'); // sns.chuanglan.sms
        $this->config = array_merge($this->config, $config['sms'] ?? []);
    }

    protected $params = [];

    /**
     * 设置消息
     * @param string $phone
     * @param mixed ...$param
     * @return void
     */
    public function addressee(string $phone, mixed ...$param){
        $this->params['PhoneNumberJson'][] = $phone;
        $this->params['SignNameJson'][] = $this->config['sign_name'];
        $this->params['TemplateParamJson'][] = $param[0];
        if(isset($param[1])) $this->params['SmsUpExtendCodeJson'][] = $param[1];
    }

    public function send(string $outId = ''){
        // *** 需用户填写部分 ***
        // fixme 必填：是否启用https
        $security = false;

        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = $this->config['access_key_id'];
        $accessKeySecret = $this->config['access_key_secret'];

        // fixme 必填: 待发送手机号。支持JSON格式的批量调用，批量上限为100个手机号码,批量调用相对于单条调用及时性稍有延迟,验证码类型的短信推荐使用单条调用的方式
        $this->params["PhoneNumberJson"] = json_encode($this->params["PhoneNumberJson"], JSON_UNESCAPED_UNICODE);

        // fixme 必填: 短信签名，支持不同的号码发送不同的短信签名，每个签名都应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $this->params["SignNameJson"] = json_encode($this->params["SignNameJson"], JSON_UNESCAPED_UNICODE);

        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $this->params["TemplateCode"] = $this->config['template_code'];

        // fixme 必填: 模板中的变量替换JSON串,如模板内容为"亲爱的${name},您的验证码为${code}"时,此处的值为
        // 友情提示:如果JSON中需要带换行符,请参照标准的JSON协议对换行符的要求,比如短信内容中包含\r\n的情况在JSON中需要表示成\\r\\n,否则会导致JSON在服务端解析失败
        $this->params["TemplateParamJson"] = json_encode($this->params["TemplateParamJson"], JSON_UNESCAPED_UNICODE);

        // todo 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段

        if(isset($this->params['SmsUpExtendCodeJson'])) $this->params['SmsUpExtendCodeJson'] = json_encode($this->params["SmsUpExtendCodeJson"], JSON_UNESCAPED_UNICODE);

        if(!empty($this->params["SmsUpExtendCodeJson"]) && is_array($this->params["SmsUpExtendCodeJson"])) {
            $this->params["SmsUpExtendCodeJson"] = json_encode($this->params["SmsUpExtendCodeJson"], JSON_UNESCAPED_UNICODE);
        }
        if($outId) $this->params['OutId'] = $outId;

        $this->params['RegionId'] = $this->config['region_id'];
        $this->params['Action'] = $this->config['action'];
        $this->params['Version'] = $this->config['version'];

        //print_r($this->params);die;

        return $this->request($accessKeyId,
            $accessKeySecret,
            $this->sendUrl,
            $this->params
        );
    }


    /**
     * 生成签名并发起请求
     *
     * @param $accessKeyId string AccessKeyId (https://ak-console.aliyun.com/)
     * @param $accessKeySecret string AccessKeySecret
     * @param $domain string API接口所在域名
     * @param $params array API具体参数
     * @param $security boolean 使用https
     * @param $method boolean 使用GET或POST方法请求，VPC仅支持POST
     * @return bool|\stdClass 返回API接口调用结果，当发生错误时返回false
     */
    protected function request($accessKeyId, $accessKeySecret, $url, $params, $security = false, $method = 'POST') {
        $apiParams = array_merge(array (
            "SignatureMethod" => "HMAC-SHA1",
            "SignatureNonce" => uniqid((string)mt_rand(0, 0xffff), true),
            "SignatureVersion" => "1.0",
            "AccessKeyId" => $accessKeyId,
            "Timestamp" => gmdate("Y-m-d\TH:i:s\Z"),
            "Format" => "JSON",
        ), $params);
        ksort($apiParams);

        $sortedQueryStringTmp = "";
        foreach ($apiParams as $key => $value) {
            $sortedQueryStringTmp .= "&" . $this->encode($key) . "=" . $this->encode($value);
        }

        $stringToSign = "${method}&%2F&" . $this->encode(substr($sortedQueryStringTmp, 1));

        $sign = base64_encode(hash_hmac("sha1", $stringToSign, $accessKeySecret . "&",true));
        $signature = $this->encode($sign);

        $curl = App::curl();
        $curl->setHeader('x-sdk-client', "php/2.0.0");
        $data = $curl->request($url, $method, "Signature={$signature}{$sortedQueryStringTmp}");
        if($data['code'] != 200) App::error()->setError('阿里云短信接口http错误：'.$data['body']);
        $body = json_decode($data['body'], true);
        if(isset($body['Code']) && $body['Code'] != 'OK') App::error()->setError('阿里云短信接口响应错误, request: '.json_encode($params, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE).' , response:'.$data['body']);
        return $body;
    }

    private function encode($str)
    {
        $res = urlencode($str);
        $res = preg_replace("/\+/", "%20", $res);
        $res = preg_replace("/\*/", "%2A", $res);
        $res = preg_replace("/%7E/", "~", $res);
        return $res;
    }
}