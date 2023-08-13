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

    public function getApiMenu(){
        $data = $this->model->table("apidoc")->where(['app' => $this->appName])->order('id', 'asc')->field('protocol,version,uri,title,groupname')->limit(5000)->select();

        $routes = [];
        foreach ($data as $k => $v){
            $key = $v['version'].'/'.$v['uri'].'-'.$v['groupname'];
            if(!isset($routes[$key])){
                $routes[$key] = $v;
            }
        }
        $groups = array_values(array_filter(array_unique(array_column($data, 'groupname'))));

        $result = [];

        foreach($groups as $k => $v){
            $result[$k]['title'] = $v;
            foreach($routes as $kk => $vv){
                if($vv['groupname'] == $v){
                    $result[$k]['apis'][] = [
                        'protocol' => $vv['protocol'],
                        'uri' => $vv['uri'],
                        'title' => $vv['title'],
                        'version' => $vv['version'],
                    ];
                    unset($routes[$kk]);
                }
            }
        }
        return $result;
    }

    /**
     * 获取ApiDoc标题
     * @param $class
     * @param $method
     * @return array|string|string[]|null
     * @throws \ReflectionException
     */
    public function getApiDocTitle(array $handler){
        //echo PHP_EOL.'#获取'.$version.'/'.$action.'的apiDoc标题#';
        //$action = explode('@', $action);
        //$class = '\\app\\'.$this->appType.'\\'.$version.'\\controller\\'.$action[0];
        $rc = new \ReflectionClass($handler[0]);
        $rc = $rc->getMethod($handler[1]);
        $comment = $rc->getDocComment();
        unset($rc);
        unset($rc);
        if($comment){
            $arr = explode("\n", $comment);
            if(isset($arr[1])){
                return str_replace('*', '', preg_replace('/\s+/',  '', $arr[1]));
            }
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

    public function getDetail(string $protocol, string $version, string $uri, $method = ''){
        $where = ['protocol' => $protocol, 'app' => $this->appName, 'uri' => $uri, 'version' => $version];
        if($method) $where['method'] = $method;
        $listData = $this->model->table('apidoc')->where($where)->order('id', 'asc')->select();
        //print_r($listData);
        $data = [];
        foreach($listData as $k => $v){
            $v['header'] = json_decode($v['header'] ?? '[]', true);
            if($v['header']){
                $header = [];
                foreach($v['header'] as $field => $head){
                    $header[] = ['field' => $field, 'description' => $head];
                }
                $v['header'] = $header;
            }

            $v['param'] = json_decode($v['param'] ?? '[]', true);
            if($v['param']){
                $param = [];
                foreach($v['param'] as $field => $rule){
                    $param[] = ['field' => $field, 'type' => $rule['type'], 'example' => is_array($rule['example']) ? json_encode($rule['example'], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE) : $rule['example'], 'description' => $rule['title'].' '.$rule['description']];
                }
                $v['param'] = $param;
            }

            $v['response'] = json_decode($v['response'] ?? '[]', true);
            $v['success'] = json_decode($v['success'] ?? '[]', true);
            $v['failure'] = json_decode($v['failure'] ?? '[]', true);
            $v['explain'] = json_decode($v['explain'] ?? '[]', true);
            $data[] = $v;
        }
        $versions = $this->model->table('apidoc')->where(['protocol' => $protocol, 'app' => $this->appName, 'uri' => $uri])->group('version')->order('version', 'desc')->select();

        return [
            'detail' => $data,
            'versions' => array_column($versions, 'version'),
        ];
    }
    public function debug(string $protocol, string $version, string $uri, string $method){
        $data = $this->model->table('apidoc')->where(['protocol' => $protocol, 'app' => $this->appName, 'version' => $version, 'uri' => $uri, 'method' => $method])->find();
        if($data){
            $success = json_decode($data['success'] ?? "[]", true);
            //$success[strtoupper($method)] = $result;
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
        return $this->model->table('apidoc')->where(['protocol' => $post['protocol'], 'app' => $this->appName, 'version' => $post['version'], 'uri' => $post['uri'], 'method' => $post['method']])->update($post);
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
