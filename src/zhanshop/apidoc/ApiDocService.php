<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / ApiDocService.php    [ 2023/3/7 18:48 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: Administrator <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\apidoc;

use zhanshop\App;
use zhanshop\Helper;

class ApiDocService
{
    protected $appName = 'http';

    protected $model;

    public function __construct(string $appName){
        $this->appName = $appName;
        Helper::mkdirs(App::runtimePath().DIRECTORY_SEPARATOR.'doc');
        $this->model = new Sqlite(App::runtimePath().DIRECTORY_SEPARATOR.'doc'.DIRECTORY_SEPARATOR.'apiDoc.db');
        $this->tableExist();
    }

    public function rollback(){
        $maxId = $this->model->table('apidoc')->where(['app' => $this->appName])->max('id');
        $this->model->table('apidoc')->where(['id' => $maxId])->delete();
    }

    public function delete(string $version, string $uri){
        $this->model->table('apidoc')->where(['app' => $this->appName, 'version' => $version, 'uri' => $uri])->delete();
    }

    public function clean(){
        $this->model->table('apidoc')->where(['app_type' => $this->appType])->delete();
    }

    protected function tableExist(){
        $count = $this->model->table('sqlite_master')->where(['type' => 'table', 'name' => 'apidoc'])->count();
        if($count == 0){
            $sql = 'CREATE TABLE "apidoc" (
  "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  "app" TEXT,
  "protocol" TEXT DEFAULT http,
  "version" TEXT,
  "uri" TEXT,
  "handler" TEXT,
  "method" TEXT,
  "title" TEXT,
  "detail" TEXT,
  "groupname" TEXT,
  "param" TEXT,
  "response" TEXT,
  "success" TEXT,
  "failure" TEXT,
  "explain" TEXT,
  CONSTRAINT "unique" UNIQUE ("app" ASC, "version" ASC, "uri" ASC, "protocol")
);';
            $this->model->execute($sql);
        }
    }


    /**
     * 获取ApiDoc分组
     * @param $class
     * @param $method
     * @return array|string|string[]|null
     * @throws \ReflectionException
     */
    public function getApiDocGroup(array $handler){
        //echo PHP_EOL.'#获取'.$version.'/'.$action.'的apiDoc分组#';
        //$action = explode('@', $action);
        //$class = '\\app\\'.$this->appType.'\\'.$version.'\\controller\\'.$action[0];
        $rc = new \ReflectionClass($handler[0]);

        $rc = $rc->getMethod($handler[1]);
        $comment = $rc->getDocComment();
        unset($rc);
        if($comment){
            $arr = explode("\n", $comment);
            foreach ($arr as $k => $v){
                if(strpos($v, '@apiGroup')){
                    $rows = array_values(array_filter(explode(' ', str_replace(["\n", "\t", "\r"], '', $v))));
                    unset($rows[0],$rows[1]);
                    return str_replace(' ', '', implode(' ', $rows));
                }
            }
        }
    }

    public function getDetail(string $protocol, string $version, string $uri){
        $data = $this->model->table('apidoc')->where(['protocol' => $protocol, 'app' => $this->appName, 'uri' => $uri, 'version' => $version])->find();
        if($data){
            $data['method'] = json_decode(strtoupper($data['method'] ?? '[]'), true);

            $data['header'] = json_decode($data['header'] ?? '[]', true);
            if($data['header'] == false){
                foreach($data['method'] as $v){
                    $data['header'][$v] = null;
                }
            }

            $data['param'] = json_decode($data['param'] ?? '[]', true);
            if($data['param'] == false){
                foreach($data['method'] as $v){
                    $data['param'][$v] = null;
                }
            }

            $data['response'] = json_decode($data['response'] ?? '[]', true);
            if($data['response'] == false){
                foreach($data['method'] as $v){
                    $data['response'][$v] = $data['response'][$v] ?? null;
                }
            }

            $data['success'] = json_decode($data['success'] ?? '[]', true);
            if($data['success'] == false){
                foreach($data['method'] as $v){
                    $data['success'][$v] = null;
                }
            }
            $data['failure'] = json_decode($data['failure'] ?? '[]', true);
            if($data['failure'] == false){
                foreach($data['method'] as $v){
                    $data['failure'][$v] = null;
                }
            }
            $data['explain'] = json_decode($data['explain'] ?? '[]', true);
            if($data['explain'] == false){
                foreach($data['method'] as $v){
                    $data['explain'][$v] = null;
                }
            }
            $data['description'] = json_decode($data['description'] ?? '[]', true);
            if($data['description'] == false){
                foreach($data['method'] as $v){
                    $data['description'][$v] = null;
                }
            }
            $explain = [];
            if($data['explain']){
                foreach($data['explain'] as $v){
                    if($v){
                        $json = json_decode($v, true);
                        $explain = $explain + $json ?? [];
                    }
                }
                $data['explain'] = $explain;
            }
        }
        $versions = $this->model->table('apidoc')->where(['protocol' => $protocol, 'app' => $this->appName, 'uri' => $uri])->group('version')->field('version')->order('id desc')->select();
        //var_dump($versions);
        return [
            'detail' => $data,
            'versions' => array_column($versions, 'version'),
        ];
    }
    public function debug($uri, $version, $result, $method){
        $data = $this->model->table('apidoc')->where(['app_type' => $this->appType, 'version' => $version, 'uri' => $uri])->find();
        if($data){
            $success = json_decode($data['success'] ?? "[]", true);
            $success[strtoupper($method)] = $result;
            $data['success'] = json_encode($success, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
            $this->model->table('apidoc')->where(['id' => $data['id']])->update($data);
        }

        return [];
    }

    public function create(array $post){
        if(!$this->model->table('apidoc')->where(['app_type' => $this->appType, 'version' => $post['version'], 'uri' => $post['uri']])->find()){
            return $this->model->table('apidoc')->insert($post);
        }
    }

    public function update(array $post){
        return $this->model->table('apidoc')->where(['app_type' => $this->appType, 'version' => $post['version'], 'uri' => $post['uri']])->update($post);
    }

    /**
     * 获取ApiDoc参数
     * @param string $version
     * @param string $action
     * @param string $requestType
     * @return array
     * @throws \ReflectionException
     */
    public function getApiDocParam(array $handler, string $action){
        $rc = new \ReflectionClass($handler[0]);

        $rc = $rc->getMethod($action);
        $comment = $rc->getDocComment();
        unset($rc);
        $data = [];
        if($comment){
            $arr = explode("\n", $comment);
            foreach ($arr as $k => $v){
                if(strpos($v, 'apiParam')){
                    $rows = array_values(array_filter(explode(' ', str_replace(["\n", "\t", "\r"], '', $v))));
                    $param = [
                        'field' => $rows[4],
                        'type' => $rows[2],
                        'verify' => $rows[3],
                        'description' => ''
                    ];
                    unset($rows[0],$rows[1],$rows[2],$rows[3],$rows[4]);
                    $param['description'] = implode(' ', $rows);
                    $data[] = $param;
                }
            }
        }
        return $data;
    }

    /**
     * 获取apiDoc描述
     * @param array $handler
     * @param string $action
     * @return string
     */
    public function getApiDoDesc(array $handler, string $action){
        $rc = new \ReflectionClass($handler[0]);
        $rc = $rc->getMethod($action);
        $comment = $rc->getDocComment();
        unset($rc);
        if($comment){
            $arr = explode("\n", $comment);
            foreach ($arr as $k => $v){
                if(strpos($v, 'apiDesc')){
                    $rows = array_values(array_filter(explode(' ', str_replace(["\n", "\t", "\r"], '', $v))));
                    unset($rows[0],$rows[1]);
                    return implode(' ', $rows);
                }
            }
        }
        return '';
    }

    /**
     * 获取apiDoc请求头参数
     * @param array $handler
     * @param string $action
     * @return array
     * @throws \ReflectionException
     */
    public function getApiDocHeader(array $handler, string $action){
        $rc = new \ReflectionClass($handler[0]);

        $rc = $rc->getMethod($action);
        $comment = $rc->getDocComment();
        unset($rc);
        $data = [];
        if($comment){
            $arr = explode("\n", $comment);
            foreach ($arr as $k => $v){
                if(strpos($v, 'apiHeader')){
                    $rows = array_values(array_filter(explode(' ', str_replace(["\n", "\t", "\r"], '', $v))));
                    $param = [
                        'field' => $rows[4],
                        'type' => $rows[2],
                        'verify' => $rows[3],
                        'description' => ''
                    ];
                    unset($rows[0],$rows[1],$rows[2],$rows[3],$rows[4]);
                    $param['description'] = implode(' ', $rows);
                    $data[] = $param;
                }
            }
        }
        return $data;
    }

    /**
     * 获取apiDoc错误解释
     * @param string $version
     * @param string $action
     * @param string $requestType
     * @return array|string
     * @throws \ReflectionException
     */
    public function getApiDocExplain(array $handler, string $action){
        $rc = new \ReflectionClass($handler[0]);
        $rc = $rc->getMethod($action);
        $comment = $rc->getDocComment();
        unset($rc);
        if($comment){
            $arr = explode("\n", $comment);
            foreach ($arr as $k => $v){
                if(strpos($v, 'apiExplain')){
                    $rows = array_values(array_filter(explode(' ', str_replace(["\n", "\t", "\r"], '', $v))));
                    unset($rows[0],$rows[1],$rows[2]);
                    return implode(' ', $rows);
                }
            }
        }
    }

    public function maintain(array $data){
        $data['app'] = $this->appName;
        $row = $this->model->table("apidoc")->where(['app' => $this->appName, 'version' => $data['version'], 'uri' => $data['uri']])->find();
        if($row){
            $this->model->table("apidoc")->where(['id' => $row['id']])->update($data);
        }else{
            $this->model->table("apidoc")->insert($data);
        }
    }

    
}
