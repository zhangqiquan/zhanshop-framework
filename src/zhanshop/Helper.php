<?php
// +----------------------------------------------------------------------
// | flow-course / Helper.php    [ 2021/10/27 10:54 上午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace zhanshop;


class Helper
{
    /**
     * 下划线转驼峰
     * 思路：step1.原字符串转小写,原字符串中的分隔符用空格替换,在字符串开头加上分隔符，step2.将字符串中每个单词的首字母转换为大写,再去空格,去字符串首部附加的分隔符.
     * @param string $uncamelized_words
     * @param string $separator
     * @return string
     */

    public static function camelize(string $uncamelized_words, string $separator='_')
    {
        $uncamelized_words = $separator. str_replace($separator, " ", strtolower($uncamelized_words));
        return ltrim(str_replace(" ", "", ucwords($uncamelized_words)), $separator );
    }

    /**
     * 驼峰命名转下划线命名
     * 思路: 小写和大写紧挨一起的地方,加上分隔符,然后全部转小写
     * @param string $camelCaps
     * @param string $separator
     * @return string
     */
    public static function uncamelize(string $camelCaps, string $separator='_')
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
    }

    /**
     * 创建目录
     * @param string $path
     * @return bool
     */
    public static function mkdirs(string $path){
        if(!is_dir($path)){
            self::mkdirs(dirname($path));
            if(!mkdir($path, 0777)){
                return false;
            }
        }
        return true;
    }

    /**
     * 生成uuid
     * @param int $num
     * @return string
     */
    public static function uuid(){
        $ip = str_replace('.','',$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');//9位
        // 最高12
        if(is_numeric($ip) == false) $ip = '127001';
        // 最高14
        $time = substr((new \DateTime())->format('ymdHisu'), 0, 14);
        $code = self::getNonceStr(32 - strlen($ip) -  strlen($time));
        return $code.$time.$ip;
    }

    /**
     * 生成php文件头注释
     */
    public static function  headComment($class){
        $year = date('Y');
        $date = date('Y-m-d H:i:s');
        return "<?php
// +----------------------------------------------------------------------
// | {$class}【系统生成】   [ {$date} ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~{$year} zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);\n\n";

    }

    /**
     * 生成一个订单号
     * @param $suffix
     * @return string
     */
    public static function orderId($suffix = false){
        $ip = str_replace('.','',$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');//9位
        if(is_numeric($ip) == false){
            $ip = '127001';
        }
        if(($s = 9- strlen($ip))>0)$ip.=str_repeat ('0', $s);
        $ip = substr($ip, 0, 9);
        $date = new \DateTime();
        $date = substr($date->format('ymdHisu'), 0, 14);
        $order_id = $date.$ip;
        if(!$suffix){
            $order_id .= rand(100, 999);
        }else{
            $order_id .= $suffix;
            if(($s = 3- strlen($suffix))>0)$order_id .= str_repeat ('0', $s);
        }
        return $order_id;

    }

    /**
     *
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return 产生的随机字符串
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

    /**
     * 获取当前域名
     * @param $scheme
     * @return mixed|string
     */
    public static function requestDomain($scheme = true){
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        if($scheme){
            $https = $_SERVER['HTTPS'] ?? 'off';
            $https = ($https == 'on') ? 'https://' : 'http://';
            $host = $https.$host;
        }
        return $host;
    }

}