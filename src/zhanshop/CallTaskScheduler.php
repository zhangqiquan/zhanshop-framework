<?php

namespace zhanshop;

class CallTaskScheduler
{
    protected string $className = "";
    protected array $vars = [];
    /**
     * 通过类名获取类的实例
     * @template Type
     * @param class-string<Type> $className
     * @return Type
     */
    public function make(string $className){
        $this->className = $className;
        return $this;
    }

    public function __call(string $name, array $arguments)
    {
        $taskHost = App::env()->get("TASK_HOST", "127.0.0.1:7201");
        $data = [
            'handler' => '\\'.$this->className.'@'.$name,
            'param' => $arguments
        ];
        return (new Curl())->request('http://'.$taskHost, 'POST', http_build_query($data));
    }
}