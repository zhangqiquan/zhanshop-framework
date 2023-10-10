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

    protected function __client(){

    }

    public function indexName(string $name){
        $this->options['index'] = $name;
        sleep(10);
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
        $this->index(['body' => $saveAll]);
        $this->options = [];
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
        $this->bulk(['body' => $saveAll]);
        $this->options = [];
    }


    public function __call(string $name, array $arguments)
    {
        try {
            return $this->$name(...$arguments);
        }catch (\Throwable $e){
            print_r($e);
        }

    }

}