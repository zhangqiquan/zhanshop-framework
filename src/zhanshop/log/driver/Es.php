<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / Es.php    [ 2023/3/13 10:52 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: Administrator <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\log\driver;

use zhanshop\App;
use zhanshop\Log;

class Es
{
    protected $client;
    protected $config = [
        'host' => 'http://192.168.1.12:9200',
        'apikey' => "QllFNzFvWUIwSUVHLVEwdlVYam06N2hMRTc0LVhSbGVXSG1jeV82aTdhdw==",
        'index' => 'zhanshop-2022',
        'max_request' => 2000,
        'timeout' => 3,
        'connect_timeout' => 3,
    ];
    protected function client(){
        if(!$this->client){
            $config = App::config()->get("log.connections");
            $config = $config['Es'] ?? [];
            $this->config = array_merge($this->config, $config);
            $this->client = \Elastic\Elasticsearch\ClientBuilder::create()
                ->setHosts([$this->config['host']])
                ->setApiKey($this->config['apikey'])
                ->build();
        }
        return $this->client;
    }
    /**
     * 日志写入
     * @access protected
     * @param array  $message     日志信息
     * @param string $destination 日志文件
     * @return bool
     */
    public function write(Log &$obj): bool
    {
        $params = [];
        $number = 0;
        $timestamp = date('Y-m-d').'T'.date('H:i:s')."+08:00";
        $microtime = microtime(true);
        while ($row = $obj->pop()){
            $number++;
            $params['body'][] = [
                'index' => [
                    '_index' => $this->config['index'],
                    '_id'    => md5($microtime.$number.rand(10000, 99999))
                ]
            ];
            $params['body'][] = [
                'timestamp' => $timestamp,
                'body'    => $row
            ];
            if($number >= $this->config['max_request']) break;
        }

        if($params){
            $params['client'][] = [
                'timeout' => $this->config['timeout'],
                // connect_timeout 控制连接完成前的curl等待时间，单位：秒
                'connect_timeout' => $this->config['connect_timeout']
            ];
            $this->client()->bulk($params);

        }
        return true;
    }
}