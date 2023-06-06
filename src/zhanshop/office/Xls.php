<?php
// +----------------------------------------------------------------------
// | youyao-v3 / Xls.php    [ 2023/6/2 下午10:50 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\office;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use zhanshop\App;
use zhanshop\office\xls\Sheet;

class Xls
{
    protected $spreadsheet;

    protected $sheetNum = 0;

    protected $worksheet = [];
    
    public function __construct()
    {
        if(file_exists(App::rootPath().DIRECTORY_SEPARATOR.'vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php') == false){
            App::error()->setError('Xls缺失依赖 请执行：composer require phpoffice/phpspreadsheet');
        }
        
        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->removeSheetByIndex(0);
    }
    /**
     * 创建一个工作工作表
     * @param string $title
     * @return Sheet
     */
    public function addSheet(string $title){
        $worksheet = $this->spreadsheet->createSheet($this->sheetNum)->setTitle($title);
        $sheet = new Sheet($worksheet);
        $this->worksheet[] = $sheet;
        $this->sheetNum++;
        return $sheet;
    }

    /**
     * 创建xls
     * @param array $data
     * @param string $fileName
     * @param array $options
     * @return void
     */
    public function save(string $savePath){
        foreach ($this->worksheet as $v){
            $v->write();
        }

        $objWriter = IOFactory::createWriter($this->spreadsheet, 'Xlsx');

        $objWriter->save($savePath);
        /* 释放内存 */
        $this->spreadsheet->disconnectWorksheets();
        unset($this->spreadsheet);
    }
}