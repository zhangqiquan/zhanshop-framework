<?php

namespace zhanshop;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class Elasticsearch
{
    /**
     * @var Client
     */
    protected mixed $client;
    protected $options = [];
    public function __construct()
    {
        $connection = App::config()->get('elasticsearch.connection');
        $client = ClientBuilder::create();
        $hosts = [];
        foreach($connection['host'] as $v){
            $hosts[] = $connection['scheme'].'://'.$v.':'.$connection['port'];
        }
        $client->setHosts($hosts);
        if($connection['user'] && $connection['pass']){
            $client->setBasicAuthentication($connection['user'], $connection['pass']);
        }

        if($connection['crt'] && file_exists($connection['crt'])){
            $client->setCABundle($connection['crt']);
        }

        if($connection['key']){
            $client->setApiKey($connection['key']);
        }

        if($connection['cloud']){
            $client->setElasticCloudId($connection['cloud']);
        }
        $this->client = $client->build();
    }

    /**
     * 原始连接
     * @return Client|mixed
     */
    public function client(){
        return $this->client;
    }

    /**
     * 设置es操作索引
     * @param string $name
     * @return $this
     */
    public function table(string $name){
        $this->options['table'] = $name;
        return $this;
    }

    /**
     * 条件
     * @param array $data
     * @return void
     */
    public function where(string $field, string $condition, string $value){
        $this->options['where'][] = [$field, $condition, $value];
        return $this;
    }

    public function count(string $field = '*'){

    }

    public function avg(string $field){

    }

    public function max(string $field){

    }

    public function min(string $field){

    }

    public function sum(string $field){

    }

    public function find(){

    }

    public function select(){

    }

    public function order(string $order){
        $this->options['order'][] = $order;
    }

    /**
     * 获取请求参数
     * @return array
     */
    protected function requestParam(){
        $params = [
            'index' => $this->options['table'],
            'body'  => []
        ];

        if(isset($this->options['order'])){
            foreach($this->options['order'] as $v){
                $arr = explode(' ', $v);
                $params['body']['sort']['should'] = [];
            }
        }

        if(isset($this->options['where'])){
            foreach($this->options['where'] as $v){
                if($v[1] == 'like'){
                    $params['body']['query']['bool']['should'][] = [
                        'wildcard' => [$v[0] => '*'.$v[2].'*']
                    ];
                }
            }
        }
        return $params;
    }

    public function finder(int $page = 1, int $limit = 20){
        $offset = ($page - 1) * $limit;
        $page--;
        // 字符串字段不支持排序
        $params = $this->requestParam();
        $params['from'] = $page;
        $params['size'] = $limit;
        $response = $this->client->search($params);
        return [
            'list' => $response['hits']['hits'],
            'total' => $response['hits']['total']['value'],
        ];
    }

    public function column(string $field, string $key){

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
        $saveData = [];
        $saveData['id'] = Helper::orderId();
        $saveData['index'] = $this->options['table'];
        $saveData['body'] = $data;
        $this->options = [];
        return $this->client->index($saveData)->asArray();
    }

    /**
     * 批量插入
     * @param array $data
     * @return void
     */
    public function insertAll(array $data){
        $saveAll = [];
        foreach($data as $k => $v){
            $orderId = Helper::orderId((string)$k);
            $saveAll[] = [
                'index' => [
                    '_index' => $this->options['table'],
                    '_id' => $orderId
                ],
            ];
            $saveAll[] = $v;
        }
        $this->client->bulk(['body' => $saveAll]);
        $this->options = [];
    }


    public function __call(string $name, array $arguments)
    {
        try {
            return $this->client->$name(...$arguments);
        }catch (\Throwable $e){
            App::error()->setError($e->getMessage());
        }
    }

}