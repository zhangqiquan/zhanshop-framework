<?php

namespace zhanshop;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class Elasticsearch
{
    protected $baseUrl = "";
    protected $userPwd = "";
    protected $options = [];
    public function __construct()
    {
        // http://elastic:zhangqiquan123@127.0.0.1:9200/_cat/indices
        $connection = App::config()->get('elasticsearch.connection');
        $auth = '';
        if($connection['user'] && $connection['pass']){
            $this->userPwd = $connection['user'].':'.$connection['pass'];
        }
        $this->baseUrl = $connection['scheme'].'://'.$connection['host'][0].':'.$connection['port'];
    }

    public function client(){
        return $this->client;
    }

    public function indexName(string $name){
        $this->options['index'] = $name;
        return $this;
    }

    /**
     * 创建索引
     * @param array $data
     * @return void
     */
    public function createIndex(array $data){
        $client->indices()->create($this->options['index'], $data);
        $this->options = [];
    }

    /**
     * 插入单条
     * @param $data
     * @return void
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\MissingParameterException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
     */
    public function insert(array $data){
        $this->options['id'] = Helper::orderId();
        $this->options['body'] = $data;

        $curl = new Curl();
        if($this->userPwd) $curl->setopt(CURLOPT_USERPWD, $this->userPwd);
        $curl->setHeader('Content-Type', 'application/json');
        $ret = $curl->request($this->baseUrl.'/'.$this->options['index'].'/_doc/'.Helper::orderId().'?pretty', 'POST', $data);
        return json_decode($ret['body'], true);
    }

    /**
     * 插入多条
     * @param array $data
     * @return mixed
     */
    public function insertAll(array $data){
        $saveAll = "";
        foreach($data as $k => $v){
            $orderId = Helper::orderId((string)$k);
            $save = [
                'index' => [
                    '_index' => $this->options['index'],
                    //'_type' => '_doc',
                    '_id' => $orderId
                ],
            ];
            $saveAll .= json_encode($save)."\n";
            $saveAll .= json_encode($v)."\n";
            //$saveAll[] = $v;
        }
        $saveAll .= "\r\n";
        $this->options = [];
        $curl = new Curl();
        if($this->userPwd) $curl->setopt(CURLOPT_USERPWD, $this->userPwd);
        $curl->setHeader('Content-Type', 'application/json');

        //print_r($saveAll);die;
        $ret = $curl->request($this->baseUrl.'/_bulk', 'POST', $saveAll, 'POST');
        return json_decode($ret['body'], true);
    }

}