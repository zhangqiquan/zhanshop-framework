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

    /**
     * 获取所有表
     * @return string[]
     */
    public function showTables(){
        $curl = new Curl();
        if($this->userPwd) $curl->setopt(CURLOPT_USERPWD, $this->userPwd);
        $curl->setHeader('Content-Type', 'application/json');
        $ret = $curl->request($this->baseUrl.'/_cat/indices?v');
        $arr = explode("\n", $ret['body']);
        unset($arr[0]);
        return array_values($arr);
    }

    /**
     * 显示状态
     * @return string[]
     */
    public function showStatus(){
        $curl = new Curl();
        if($this->userPwd) $curl->setopt(CURLOPT_USERPWD, $this->userPwd);
        $curl->setHeader('Content-Type', 'application/json');
        $ret = $curl->request($this->baseUrl.'/_cat/health?v');
        $arr = explode("\n", $ret['body']);
        unset($arr[0]);
        return array_values($arr);
    }

    /**
     * 清空数据表
     * @return void
     */
    public function truncate(){
        $curl = new Curl();
        if($this->userPwd) $curl->setopt(CURLOPT_USERPWD, $this->userPwd);
        $curl->setHeader('Content-Type', 'application/json');
        $ret = $curl->request($this->baseUrl.'/'.$this->options['index'], 'DELETE');
        return true;
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
    public function createIndex(array $data, array $settings = []){
        /**
         * {
        "settings": {
        "number_of_shards": 3,
        "number_of_replicas": 2
        },
        "mapping": {
        "_doc": {
        "properties": {
        "commodity_id": {
        "type": "long"
        },
        "commodity_name": {
        "type": "text"
        },
        "picture_url": {
        "type": "keyword"
        },
        "price": {
        "type": "double"
        }
        }
        }
        }
        }



        {
        "doctor": {
        "aliases": {},
        "mappings": {
        "properties": {
        "id": {
        "type": "long"
        },
        "name": {
        "type": "text",
        "fields": {
        "keyword": {
        "type": "keyword",
        "ignore_above": 256
        }
        }
        },
        "proficient": {
        "type": "text",
        "fields": {
        "keyword": {
        "type": "keyword",
        "ignore_above": 256
        }
        }
        }
        }
        },
        "settings": {
        "index": {
        "routing": {
        "allocation": {
        "include": {
        "_tier_preference": "data_content"
        }
        }
        },
        "number_of_shards": "1",
        "provided_name": "doctor",
        "creation_date": "1697448853800",
        "number_of_replicas": "1",
        "uuid": "fqhMEfJDS6eiLjBFt4Y5DA",
        "version": {
        "created": "8080199"
        }
        }
        }
        }
        }

         */
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
                    '_id' => $orderId
                ],
            ];
            $saveAll .= json_encode($save)."\n";
            $saveAll .= json_encode($v)."\n";
        }
        $saveAll .= "\r\n";
        $this->options = [];
        $curl = new Curl();
        if($this->userPwd) $curl->setopt(CURLOPT_USERPWD, $this->userPwd);
        $curl->setHeader('Content-Type', 'application/json');
        $ret = $curl->request($this->baseUrl.'/_bulk', 'POST', $saveAll, 'POST');
        return json_decode($ret['body'], true);
    }

    /**
     * 分页查询
     * @param int $page
     * @param in $limit
     * @return void
     */
    public function finder(int $page = 1, int $limit = 20){

    }

    /**
     * 查询条件
     * @return void
     */
    public function where(){

    }

    /**
     * 执行sql
     * @param string $sql
     * @return void
     */
    public function query(string $sql){

    }

    /**
     * 更新条件删除
     * @return void
     */
    public function delete(){
        // 根据条件删除
    }

}