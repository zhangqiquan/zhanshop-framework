<?php
// +----------------------------------------------------------------------
// | flow-course / Doc.php    [ 2021/10/29 2:47 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace zhanshop\console\command\apicreate;


use zhanshop\apidoc\ApiDocService;
use zhanshop\App;
use zhanshop\console\Input;

class Doc
{
    /**
     * 创建api文档
     * @param Input $input
     */
    public static function create(Input $input, string $appType){
        $action = $input->param('class').'@'.$input->param('method');
        $param = [];
        $explain = [];
        $service = new ApiDocService($appType);
        foreach($input->param('reqtype') as $vv){
            try {
                $param[$vv] = $service->getApiDocParam($input->param('version'), $action, strtolower($vv)); // 拿到文档参数
                $explain[$vv] = $service->getApiDocExplain($input->param('version'), $action, strtolower($vv)); // 拿到错误代码解析
            }catch (\Throwable $e){

            }
        }
        $service->create([
            'version' => $input->param('version'),
            'uri' => $input->param('uri'),
            'method' => strtoupper(json_encode($input->param('reqtype'))),
            'action' => $action,
            'title' => $input->param('title'),
            'groupname' => $input->param('groupname'),
            'param' => json_encode($param, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE),
            'explain' => json_encode($param, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE),
        ]);
        echo PHP_EOL.'apiDoc构造成功'.PHP_EOL;
    }

}