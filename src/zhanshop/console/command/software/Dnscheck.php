<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / Ping.php    [ 2023/8/3 下午2:20 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\console\command\software;

use zhanshop\console\Command;
use zhanshop\console\Input;
use zhanshop\console\Output;

class Dnscheck extends Command
{
    public function configure()
    {
        $this->setTitle('dns检查')->setDescription('对指定域名进行DNS检查');
    }

    public function execute(Input $input, Output $output)
    {
        $host = $input->input('host', '请输入需要ping的地址');
        echo "\n";
        $ipv4 = gethostbyname($host);
        echo 'IP地址： '.$ipv4.PHP_EOL;
        try {
            echo '主机名： '.gethostbyaddr($ipv4).PHP_EOL;
        }catch (\Throwable $e){
            echo $e->getMessage().PHP_EOL;
        }
        echo "DNS通信： ".checkdnsrr($host).PHP_EOL;
        getmxrr($host, $mxrr);
        echo "DNS-MX记录 => ".print_r($mxrr, true);
        try{

            echo "DNS-A纪录 => ".print_r(dns_get_record($host, DNS_A), true);
            echo "DNS-CNAME纪录 => ".print_r(dns_get_record($host, DNS_CNAME), true);
            echo "DNS-TXT纪录 => ".print_r(dns_get_record($host, DNS_TXT), true);
        }catch (\Throwable $e){
            echo $e->getMessage().PHP_EOL;
        }

    }
}
