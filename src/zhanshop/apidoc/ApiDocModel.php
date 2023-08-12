<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / apiDocModel.php    [ 2023/8/11 14:45 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\apidoc;

use zhanshop\App;
use zhanshop\Helper;

class ApiDocModel
{
    protected Sqlite $model;

    public function __construct(){
        Helper::mkdirs(App::runtimePath().DIRECTORY_SEPARATOR.'doc');
        $this->model = new Sqlite(App::runtimePath().DIRECTORY_SEPARATOR.'doc'.DIRECTORY_SEPARATOR.'apiDoc.db');
        $this->tableExist();
    }

    /**
     * 获取查询对象
     * @return Sqlite
     */
    public function getQuery(){
        return $this->model;
    }

    protected function tableExist()
    {
        try {
            $sql = 'CREATE TABLE "apidoc" (
"id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
"app" TEXT,
"protocol" TEXT DEFAULT http,
"version" TEXT,
"uri" TEXT,
"handler" TEXT,
"method" TEXT,
"title" TEXT,
"description" TEXT,
"detail" TEXT,
"groupname" TEXT,
"header" TEXT,
"param" TEXT,
"response" TEXT,
"success" TEXT,
"failure" TEXT,
"explain" TEXT,
CONSTRAINT "unique" UNIQUE ("app" ASC, "version" ASC, "uri" ASC, "protocol" ASC, "method")
);
UPDATE "main"."sqlite_sequence" SET seq = 1 WHERE name = \'apidoc\';';
            $this->model->execute($sql);
        }catch (\Throwable $e){

        }

    }
}