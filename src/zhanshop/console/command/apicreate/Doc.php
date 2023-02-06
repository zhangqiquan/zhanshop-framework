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


use zhanshop\App;
use zhanshop\console\Input;
use zhanshop\Service\ApiDocService;

class Doc
{
    /**
     * 创建api文档
     * @param Input $input
     */
    public static function create(Input $input){
        $action = $input->param('class').'@'.$input->param('method');
        $service = App::service()->get(ApiDocService::class);
        $apiDoc = $service->get($input->param('version'), $input->param('uri'));
        // 如果apiDoc 不存在就创建
        if(!$apiDoc){
            $arr = [
                'version' => $input->param('version'),
                'uri' => $input->param('uri'),
                'method' => strtoupper(json_encode($input->param('reqtype'))),
                'action' => $action,
                'title' => $input->param('title'),
                'groupname' => $input->param('groupname'),
            ];
            $service->create($arr);
            echo PHP_EOL.'apiDoc构造成功'.PHP_EOL;
        }
    }

}