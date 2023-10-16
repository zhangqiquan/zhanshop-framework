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
     * 使用sql查询【仅限查询 不会返回_index, _id等字段】
     * @param string $sql
     * @return array
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
     */
    public function query(string $sql){
        $body = [
            'body' => [
                'query' => $sql, // 只能使用区间搜索分页
            ],
        ];
        $data = [];
        $this->client->sql()->query($body);
        $resp = $this->client->sql()->query($body)->asArray();
        foreach($resp['rows'] as $k => $v){
            foreach($v as $kk => $vv){
                $data[$k][$resp['columns'][$kk]['name']] = $vv;
            }
        }
        return $data;
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
     * 获取请求参数
     * @return array
     */
    /*protected function requestParam(){
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
                }else if($v[1] == '='){
                    $params['body']['query']['match'][$v[0]] = $v[2];
                }
            }
        }
        return $params;
    }*/


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

    /**
     * 更新
     * @param array $data
     * @param int $id
     * @return array
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\MissingParameterException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
     */
    public function update(array $data, int $id){
        $params = [
            'index' => $this->options['table'],
            'id' => $id,
            'body' => [
                'doc' => $data,
            ],
        ];
        return $this->client->update($params)->asArray();
    }

    /**
     * 删除
     * @param int $id
     * @return array
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\MissingParameterException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
     */
    public function delete(int $id){
        $params = [
            'index' => $this->options['table'],
            'id' => $id
        ];

        return $this->client->delete($params)->asArray();
    }

    /**
     * 删除表
     * @param string $table
     * @return void
     */
    public function drop(string $table){
        $params = [
            'index' => $table,
        ];
        try {
            return $this->client->indices()->delete($params)->asArray();
        }catch (\Throwable $e){
            return $e->getMessage();
        }

    }
}