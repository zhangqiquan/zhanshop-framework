<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / Excel.php    [ 2023/3/20 9:45 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: Administrator <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * php composer require phpoffice/phpspreadsheet (先安装后再使用)
 */
class Excel
{

    public function toArray(string $filePath){
        //$objExcel = new Spreadsheet();
        $spreadsheet = IOFactory::load($filePath);
        $data = [];
        for($i = 0; $i < $spreadsheet->getSheetCount(); ++$i){
            $worksheet = $spreadsheet->getSheet($i);
            $data[] = [
                'title' => $worksheet->getTitle(),
                'body'  => $worksheet->toArray(null, true, true, true)
            ];
        }
        return $data;
    }
}