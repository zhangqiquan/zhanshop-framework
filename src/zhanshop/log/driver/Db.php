<?php
// +----------------------------------------------------------------------
// | flow-course / Db.php    [ 2021/10/27 4:25 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace zhanshop\log\driver;


use zhanshop\App;
use zhanshop\Log;

class Db extends File
{
    protected $config = [
        'max_request' => 2000,
        'table' => 'system_logs', // 这个表需要包含3个字段 id bigint 递增、timestamp varchar(20) 、 body text
    ];

    public function __construct(){
        $config = App::config()->get("log.connections");
        $config = $config['File'] ?? [];
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 日志写入
     * @access protected
     * @param array  $message     日志信息
     * @param string $destination 日志文件
     * @return bool
     */
    public function write(Log &$obj): bool
    {

        $insertAll = [];
        $timestamp = date('Y-m-d H:i:s');
        $number = 0;
        while ($row = $obj->pop()){
            $insert = [
                'timestamp' => $timestamp,
                'body' => $row,
            ];
            $insertAll[] = $insert;
            $number++;
            if($number >= $this->config['max_request']) break;
        }

        if($insertAll){
            //print_r($insertAll);
            App::database()->model($this->config['table'])->insertAll($insertAll);
        }
        return true;
    }
}