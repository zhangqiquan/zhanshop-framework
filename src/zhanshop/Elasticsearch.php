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
    public function __construct()
    {
        $client = ClientBuilder::create()
            ->setHosts(['localhost:9200'])
            ->build();
    }
}