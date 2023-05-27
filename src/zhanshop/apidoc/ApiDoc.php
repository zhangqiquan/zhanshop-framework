<?php

namespace zhanshop\apidoc;

use zhanshop\App;
use zhanshop\Error;

class ApiDoc
{
    /**
     * 获取sqlDb
     * @return Sqlite
     * @throws \Exception
     */
    protected function db(){
        $database = 'apidoc';
        $path = App::runtimePath().DIRECTORY_SEPARATOR.'doc'.DIRECTORY_SEPARATOR.$database.'.db';
        if(!file_exists($path)) App::error()->setError($database.'库文件不存在');
        return new Sqlite($path);
    }

    /**
     * 获取所有的菜单
     * @param string $url
     * @return void
     */
    public function getApidoc(){
        $data = $this->db()->table("apimenu")->limit(3000)->order('id asc')->order('sortrank asc')->field('id,title as name, parent_id as pid,icon')->select();
        // 查询所有的文档
        $docs = $this->db()->table("apidocs")->whereIn('menu_id', array_column($data, 'id'))->group('type,version,uri')->field('id,title,type,version,menu_id,uri')->select();
        foreach($data as $k => $v){
            foreach($docs as $kk => $vv){
                if($vv['menu_id'] == $v['id']){
                    $data[] = [
                        'id' =>	50000000 + $vv['id'],
                        'name' =>	$vv['title'],
                        'pid' =>	$v['id'],
                        'target' =>	"_self",
                        'icon' => "",
                        'url' => 'doc/'.$vv['type'].'.html?version='.$vv['version'].'&uri='.$vv['uri'],
                        'sortrank' => 50
                    ];
                    unset($docs[$kk]);
                }
            }
        }
        return $data;
    }
}