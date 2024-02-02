<?php

namespace zhanshop;

abstract class Script
{
    abstract public function execute();

    /**
     * 获取参数
     * @param int $index
     * @return mixed
     */
    public function param(int $index = -1)
    {
        if($index == -1) return $_SERVER['argv'];

        return $_SERVER['argv'][$index];
    }
}