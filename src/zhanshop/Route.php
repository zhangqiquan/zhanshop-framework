<?php
// +----------------------------------------------------------------------
// | framework / Route.php    [ 2021/10/30 10:08 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2022 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop;

use zhanshop\route\Dispatch;
use zhanshop\route\Group;
use zhanshop\route\Rule;

class Route
{
    protected $rules = [];
    protected $group = null;
    public function rule(string $method, string $uri, array $handler) :Rule{
        $rule = new Rule($method, $uri, $handler);
        if($this->group){
            $this->rules[] = $rule;
        }else{
            App::make(Dispatch::class)->regRoute($rule);
        }
        return $rule;
    }


    /**
     * 分组路由
     * @param string $name
     * @param callable $fun
     * @return void
     */
    public function group(string $name, callable $fun){
        $group = new Group($name, $fun);
        $this->group = $group;
        $group->execute();
        $this->group = null;
        $group->bindRoute($this->rules);
        return $group;
    }
}