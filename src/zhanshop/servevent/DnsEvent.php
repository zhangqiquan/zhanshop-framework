<?php
// +----------------------------------------------------------------------
// | zhanshop-dns / DnsEvent.php    [ 2023/12/31 下午6:33 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\servevent;

use zhanshop\App;
use zhanshop\dns\Decoder;
use zhanshop\dns\Encoder;
use zhanshop\dns\RecordType;
use zhanshop\dns\RecordTypeEnum;
use zhanshop\dns\ResourceRecord;

class DnsEvent
{
    /**
     * dns查询
     * @param string $clientIp 客户端IP
     * @param string $domain 需要解析的域名
     * @param int $type 解析的类型
     * @return array
     */
    public function dnsQuery(string $clientIp, string $domain, int $type = 1) :array
    {
        return [];
    }

    /**
     * @param \Swoole\Server $server
     * @param string $data
     * @param array $clientInfo
     * @return void
     */
    public function onPacket($server, $data, $clientInfo) :void{
        try {
            $request = Decoder::decodeMessage($data);
            $answers = [];
            foreach ($request->getQuestions() as $v){
                $records = $this->dnsQuery($clientInfo['address'], $v->getName(), $v->getType());
                if($records){
                    foreach($records as $vv){
                        $resourceRecord = new ResourceRecord();
                        $resourceRecord->setName($v->getName());
                        $resourceRecord->setType($vv['type'] ?? 1);
                        $resourceRecord->setTtl($vv['ttl'] ?? 600);
                        $resourceRecord->setRdata($vv['record'] ?? '127.0.0.1');
                        $resourceRecord->setClass(1);
                        $answers[] = $resourceRecord;
                    }
                }else{
                    try {
                        $type = constant('DNS_'.RecordTypeEnum::$names[$v->getType()]);
                        $records = dns_get_record(rtrim($v->getName(), '.'), $type);
                        foreach($records as $vv){
                            $resourceRecord = new ResourceRecord();
                            $resourceRecord->setName($v->getName());
                            $resourceRecord->setType($v->getType());
                            $resourceRecord->setTtl($vv['ttl'] ?? 600);
                            $resourceRecord->setRdata($vv['ip'] ?? '127.0.0.1');
                            $resourceRecord->setClass(1);
                            $answers[] = $resourceRecord;
                        }
                    }catch (\Throwable $e){

                    }
                }
            }
            $header = $request->getHeader();
            $header->setResponse(true);
            $header->setAuthoritative(true);
            $header->setRecursionDesired(true);
            $request->setHeader($header);
            $request->setAnswers($answers);
            $server->sendto($clientInfo['address'], $clientInfo['port'], Encoder::encodeMessage($request));
        }catch (\Throwable $e){
            echo $e->getMessage().PHP_EOL;
            echo $e->getFile().':'.$e->getLine();
            App::log()->push($e->getMessage(), "ERROR");
        }

    }
}