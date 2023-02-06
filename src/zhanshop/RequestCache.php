<?php
// +----------------------------------------------------------------------
// | flow-course / Request类    [ 2021/10/22 4:52 下午 ]
// +-------------------------------------------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace zhanshop;


class RequestCache
{
    /**
     * 请求缓存的数据
     * @var array
     */
    public $data = [];

    public function __construct(){
    }

    /**
     * 设置请求缓存
     * @param string $key
     * @param mixed $val
     * @param $expire
     * @return void
     */
    public function set(string $key, mixed &$val, $expire = 60){
        $this->data[$key] = [
            'val' => $val,
            'expire' => time() + $expire
        ];
    }

    /**
     * 获取请求缓存
     * @param string $key
     * @return mixed|null
     */
    public function &get(string $key){
        $val = null;
        if(isset($this->data[$key]['expire'])){
            if($this->data[$key]['expire'] < time()){
                unset($this->data[$key]);
                return $val;
            }else{
                if($this->data[$key]['val'] != false){
                    return $this->data[$key]['val'];
                }
            }
        }
        return $val;
    }
}