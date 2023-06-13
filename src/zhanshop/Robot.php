<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / Robot.php    [ 2023/3/11 13:27 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop;
/**
 * 第三方通知机器人
 */
class Robot
{
    protected $dirver;

    public function __construct(){
        $type = App::env()->get('PUSH_REBOT_TYPE');
        $url = App::env()->get('PUSH_REBOT_URL');
        if($url && $type){
            if(strpos($type, '\\') === false) $dirver = '\\zhanshop\\robot\\'.ucfirst($type);
            $this->dirver = new $dirver($url);
        }else{
            $this->dirver = $this;
        }
    }

    /**
     * 发送文本消息
     * @param string $msg
     * @return void
     */
    public function sendText(string $msg){
        return 123;
    }

    /**
     * 通过机器人发送消息
     * @param string $msg
     * @return array|bool|string
     */
    public function send(string $msg){
        $msg = '['.date('Y-m-d H:i:s').'] '.$msg;
        $ret = $this->dirver->sendText($msg);
    }
}