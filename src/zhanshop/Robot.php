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
        $robot = App::config()->get("sns.robot");
        if(isset($robot['type']) && $robot['type'] && isset($robot['url']) && $robot['url']){
            $dirver = $robot['type'];
            if(strpos($robot['type'], '\\') === false) $dirver = '\\zhanshop\\robot\\'.ucfirst($robot['type']);
            $this->dirver = new $dirver($robot['url']);
        }
    }

    /**
     * 通过机器人发送消息
     * @param string $msg
     * @return array|bool|string
     */
    public function sendMsg(string $msg){
        if($this->dirver) $this->dirver->sendText($msg);
    }
}