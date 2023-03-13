<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / QyWeChat.php    [ 2023/3/11 13:40 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\robot;

use zhanshop\App;

class QyWeChat
{
    protected $url;

    public function __construct(string $url){
        $this->url = $url;
    }

    public function sendText(string $msg){
        $curl = App::curl();
        try {
            return $curl->request($this->url, 'POST', [
                'msgtype' => 'text',
                'text' => [
                    'content' => $msg
                ]
            ], 'application/json');
        }catch (\Throwable $e){
            App::log()->push($e->getMessage(), 'ERROR');
        }
        return false;
    }
}