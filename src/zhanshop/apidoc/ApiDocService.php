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

class ApiDocService
{
    protected $appType = 'http';

    protected $model;

    public function __construct(string $appType){
        $this->appType = $appType;
        $this->model = new Sqlite();
        $this->tableExist();
    }

    public function rollback(){
        $maxId = $this->model->table('apidoc')->where(['app_type' => $this->appType])->max('id');
        $this->model->table('apidoc')->where(['id' => $maxId])->delete();
    }

    public function delete(string $version, string $uri){
        $this->model->table('apidoc')->where(['app_type' => $this->appType, 'version' => $version, 'uri' => $uri])->delete();
    }

    public function clean(){
        $this->model->table('apidoc')->where(['app_type' => $this->appType])->delete();
    }

    protected function tableExist(){
        try {
            $this->model->query("select * from apidoc limit 1");
        }catch (\Throwable $e){
            if($e->getCode() == 10501){
                $sql = 'CREATE TABLE IF NOT EXISTS "apidoc" ( "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, "app_type" TEXT, "version" TEXT, "action" TEXT, "uri" TEXT,  "method" TEXT,
    "title" TEXT, "detail" TEXT,  "groupname" TEXT, "param" TEXT, "response" TEXT, "success" TEXT, "failure" TEXT, "explain" TEXT );
    UPDATE "main"."sqlite_sequence" SET seq = 1 WHERE name = \'apidoc\';
    PRAGMA foreign_keys = true;';
                $this->model->execute($sql);
            }
        }
    }

    public function getApiMenu(){
        $subSql = "select id,version,uri,title,groupname from ".' apidoc where app_type = "'.$this->appType.'" order by `id` desc';
        $sql = "select * from({$subSql}) as a order by version asc";
        $data = $this->model->query($sql);
        $uniqueData = [];
        foreach($data as $k => $v){
            $uniqueData[$v['uri']] = $v;
        }
        $data = array_values($uniqueData);
        $groups = array_values(array_unique(array_column($data, 'groupname')));

        $result = [];

        foreach($groups as $k => $v){
            $result[$k]['title'] = $v;
            foreach($data as $kk => $vv){
                if($vv['groupname'] == $v){
                    $result[$k]['apis'][] = [
                        'uri' => $vv['uri'],
                        'title' => $vv['title'],
                        'version' => $vv['version'],
                    ];
                    unset($data[$kk]);
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
    public function getApiDocTitle(string $version, string $action){
        echo PHP_EOL.'#获取'.$version.'/'.$action.'的apiDoc标题#';
        $action = explode('@', $action);
        $class = '\\app\\'.$this->appType.'\\'.$version.'\\controller\\'.$action[0];
        $rc = new \ReflectionClass($class);
        $rc = $rc->getMethod($action[1]);
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
    public function getApiDocGroup(string $version, string $action){
        echo PHP_EOL.'#获取'.$version.'/'.$action.'的apiDoc分组#';
        $action = explode('@', $action);
        $class = '\\app\\'.$this->appType.'\\'.$version.'\\controller\\'.$action[0];
        $rc = new \ReflectionClass($class);

        $rc = $rc->getMethod($action[1]);
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

    public function getDetail(string $version, string $uri){
        $data = $this->model->table('apidoc')->where(['app_type' => $this->appType, 'uri' => $uri, 'version' => $version])->find();
        if($data){
            $data['method'] = json_decode(strtoupper($data['method'] ?? '[]'), true);
            $data['param'] = json_decode($data['param'] ?? '[]', true);
            $data['success'] = json_decode($data['success'] ?? '[]', true);
            $data['failure'] = json_decode($data['failure'] ?? '[]', true);
            $data['explain'] = json_decode($data['explain'] ?? '[]', true);
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
        $versions = $this->model->table('apidoc')->where(['app_type' => $this->appType, 'uri' => $uri])->group('version')->field('version')->order('id desc')->select();
        //var_dump($versions);
        return [
            'data' => $data,
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
    public function getApiDocParam(string $version, string $action, string $requestType){
        echo PHP_EOL.'#获取'.$version.'/'.$action.'的service的'.$requestType.'参数#';
        $action = explode('@', $action);
        $class = '\\app\\'.$this->appType.'\\'.$version.'\\service\\'.$action[0].'Service';
        $rc = new \ReflectionClass($class);

        $rc = $rc->getMethod($requestType.ucwords($action[1]));
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
     * 获取apiDoc错误解释
     * @param string $version
     * @param string $action
     * @param string $requestType
     * @return array|string
     * @throws \ReflectionException
     */
    public function getApiDocExplain(string $version, string $action, string $requestType){
        echo PHP_EOL.'#获取'.$version.'/'.$action.'的service的'.$requestType.'异常解释#';
        $action = explode('@', $action);
        $class = '\\app\\'.$this->appType.'\\'.$version.'\\service\\'.$action[0].'Service';
        $rc = new \ReflectionClass($class);
        $rc = $rc->getMethod($requestType.ucwords($action[1]));
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
        $data['app_type'] = $this->appType;
        $row = $this->model->table("apidoc")->where(['app_type' => $this->appType, 'version' => $data['version'], 'uri' => $data['uri']])->find();
        if($row){
            $this->model->table("apidoc")->where(['id' => $row['id']])->update($data);
        }else{
            $this->model->table("apidoc")->insert($data);
        }
    }
}