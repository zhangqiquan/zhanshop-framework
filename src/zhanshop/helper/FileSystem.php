<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / FileSystem.php    [ 2023/8/19 15:48 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\helper;

use zhanshop\App;

class FileSystem
{
    /**
     * 文件截取
     * @param string $path
     * @param int $start
     * @param int $line
     * @return string
     */
    public static function extract(string $path, int $start, int $end){
        $handle = fopen($path, 'r');
        if (!$handle) {
            App::error()->setError('文件打开失败');
        }
        $lineNum = 1;
        $codeStr = "";
        while (false !== ($char = fgets($handle,10240))) {        //循环读取文件内容
            if($lineNum >= $start && $lineNum <= $end){
                $codeStr .= $char;
            }
            $lineNum++;
        }
        fclose($handle);
        return $codeStr;
    }
}