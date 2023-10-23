<?php

namespace zhanshop\service;

use zhanshop\Request;
use zhanshop\Response;
use zhanshop\service\git\Gitee;

class Git
{
    /**
     * git推送
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function push(Request &$request, Response &$response){
        $type = '\\zhanshop\\service\\git\\'.strtoupper($request->get('type'));
        $obj = new $type();
        return $obj->push($request, $response);
    }
}