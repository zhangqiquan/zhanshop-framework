<?php
// +----------------------------------------------------------------------
// | zhanshop-php / WatchCode.php    [ 2023/2/2 09:32 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\console;

class WatchCode
{
    protected static $watchPath;
    /**
     * 排除目录
     * @var string[]
     */
    public static $excludeDir = ['vendor', 'runtime', 'public', 'test'];

    protected static $hashes = [];

    /**
     * 监视的文件后缀
     * @var string
     */
    public static $watchExt = 'php';

    /**
     * 初始化【像这种可能比较耗时的任务丢到Task, 还有日志的循环和写入也丢进去】
     * @param string $watchPath
     * @return int
     */
    public static function init(string $watchPath = '.'){
        if(self::$watchPath == false) self::$watchPath = $watchPath;
        self::$watchPath = $watchPath;
        $files = self::getFiles(self::$watchPath);
        self::$hashes = array_combine($files, array_map([WatchCode::class, 'fileHash'], $files));
        $count = count(self::$hashes);
        return $count;

    }

    /**
     * 是否变化
     * @return bool
     */
    public static function isChange() :bool{
        $files = self::getFiles(self::$watchPath);
        $newHashes = array_combine($files, array_map([WatchCode::class, 'fileHash'], $files));
        foreach($newHashes as $k => $v){
            if(isset(self::$hashes[$k]) == false){
                //echo '文件新增:'.$k.PHP_EOL;
                // 有新增文件
                return true;
            }elseif (isset(self::$hashes[$k]) && self::$hashes[$k] != $v){
                //echo '文件发生变化:'.$k.PHP_EOL;
                // 文件发生变化
                return true;
            }
        }

        foreach(self::$hashes as $k => $v){
            // 如果老的文件不在新的中被删除了
            if(isset($newHashes[$k]) == false){
                return true;
            }
        }
        // 如果新增 或者 变化
        return false;
    }

    /**
     * 获取所有文件
     * @param string $path
     * @return array
     */
    protected static function getFiles(string $path){
        $directory = new \RecursiveDirectoryIterator($path);
        $filter = new WatchCodeFilter($directory);
        $iterator = new \RecursiveIteratorIterator($filter);
        return array_map(function ($fileInfo) {
            return $fileInfo->getPathname();
        }, iterator_to_array($iterator));

    }

    /**
     * 获取文件hash值
     * @param string $pathname
     * @return string
     */
    protected static function fileHash(string $pathname): string
    {
        $contents = filemtime($pathname); // 拿文件最后修改时间
        if (false === $contents) {
            return 'deleted';
        }
        return md5((string)$contents);
    }


}

class WatchCodeFilter extends \RecursiveFilterIterator
{
    public function accept() :bool
    {
        if ($this->current()->isDir()) {
            if (preg_match('/^\./', $this->current()->getFilename())) {
                return false;
            }
            return !in_array($this->current()->getFilename(), WatchCode::$excludeDir);
        }
        $list = array_map(function (string $item): string {
            return "\.$item";
        }, explode(',', WatchCode::$watchExt.','.($_SERVER['APP_ENV'] ?? 'dev')));
        $list = implode('|', $list);
        $int = preg_match("/($list)$/", $this->current()->getFilename());
        return boolval($int);
    }
}
