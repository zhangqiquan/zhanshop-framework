<?php
// +----------------------------------------------------------------------
// | zhanshop-swoole / Command.php    [ 2021/12/30 5:06 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\console;

abstract class Command
{
    /**
     * 配置信息
     * @return mixed
     */
    abstract public function configure();
    /**
     * 执行业务
     * @param Input $input
     * @param Output $output
     * @return mixed
     */
    abstract public function execute(Input $input, Output $output);

    /**
     * 设置标题
     * @param string $title
     * @return Command
     */
    protected function setTitle(string $title = '')
    {
        $this->title = $title;

        return $this;
    }
    /**
     * 设置描述
     * @param string $description
     * @return Command
     */
    protected function setDescription(string $description = '')
    {
        $this->description = $description;

        return $this;
    }

    /**
     * 获取标题
     */
    public function getTitle(){
        return $this->title ?? '';
    }

    /**
     * 获取描述
     * @return mixed
     */
    public function getDescription(){
        return $this->description ?? '';
    }
}