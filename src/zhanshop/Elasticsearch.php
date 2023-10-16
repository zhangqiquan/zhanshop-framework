<?php

namespace zhanshop;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class Elasticsearch
{
    protected $baseUrl = "";
    protected $options = [];
    public function __construct()
    {
        // http://elastic:zhangqiquan123@127.0.0.1:9200/_cat/indices
        $connection = App::config()->get('elasticsearch.connection');
        $auth = '';
        if($connection['user'] && $connection['pass']){
            $auth = $connection['user'].':'.$connection['pass'].'@';
        }
        $this->baseUrl = $connection['scheme'].'://'.$auth.$connection['host'][0].':'.$connection['port']; // 暂时仅执行用户和密码
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
        $curl->setHeader('Content-Type', 'application/json');
        $ret = $curl->request($this->baseUrl.'/'.$this->options['index'].'/_doc/'.Helper::orderId().'?pretty', 'POST', $data);
        return json_decode($ret['body'], true);
    }

    public function insertAll(array $data){
        $saveAll = [];
        foreach($data as $k => $v){
            $orderId = Helper::orderId((string)$k);
            $saveAll[] = [
                'index' => [
                    '_index' => $this->options['index'],
                    '_id' => $orderId
                ],
            ];
            $saveAll[] = $v;
        }

        $this->options = [];
        $this->client->bulk(['body' => $saveAll])->asArray();
    }

}