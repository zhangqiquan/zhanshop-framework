<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / Menu.php    [ 2023/3/1 23:12 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: Administrator <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\console\command\admin;

use zhanshop\App;
use zhanshop\console\Input;

class Menu
{
    protected Input $input;
    public function __construct($input)
    {
        $this->input = $input;
        $this->input->input('id', '菜单id');
        $this->input->input('title', '菜单名称');
        $this->input->input('parent_id', '菜单父id,可在后台查看');
        $this->input->input('target', '当前菜单打开方式', '_self');
        $this->input->input('icon', 'ico图标', 'mdi mdi-circle-outline');
        $this->input->input('table_names', '关联的数据表名');
        $this->input->input('page', '前端自定义page地址', '');
        $this->input->input('sortrank', '菜单排序值', 50);
        $this->input->input('is_hidden', '是否隐藏', '0');
    }

    public function create(){
        if(App::database()->model('system_menu')->where(['id' => $input->param('id')])->find()){
            App::database()->model('system_menu')->where(['id' => $input->param('id')])->update($input->all());
        }else{
            App::database()->model('system_menu')->insert($input->all());
        }
    }
}