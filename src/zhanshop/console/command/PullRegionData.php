<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / PullRegionData.php    [ 2023/4/12 15:43 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);
namespace zhanshop\console\command;

use zhanshop\App;
use zhanshop\console\Command;
use zhanshop\console\Input;
use zhanshop\console\Output;
use zhanshop\Curl;
use zhanshop\Document;
use zhanshop\Helper;

class PullRegionData extends Command
{
    protected $url = '';
    protected $regionDir = '';
    public function configure(){
        $this->setTitle('拉取中国大陆地址库')->setDescription('全国统计用区划代码和城乡划分代码生成按省为划分的文件');
    }

    protected function getErrorProvince(){
        if(file_exists($this->regionDir.'/error-province.log')){
            return file_get_contents($this->regionDir.'/error-province.log');
        }
        return -1;
    }

    public function execute(Input $input, Output $output){
        $this->regionDir = App::runtimePath().'/region';
        Helper::mkdirs($this->regionDir);
        $url = App::env()->get('REGION_URL', 'http://www.stats.gov.cn/sj/tjbz/tjyqhdmhcxhfdm/2023/index.html');
        $this->url = $url;

        $curl = new Curl();
        $body = $curl->request($this->url)['body'];

        $errorProvince = (int)$this->getErrorProvince();

        $document = new Document($body);
        $allA = $document->getElementsByClassName("provincetr")->getElementsByTagName('a');
        $texts = $allA->innerText();
        $hrefs = $allA->getAttribute('href');

        $prefix = dirname($this->url);
        foreach ($texts as $k => $v){
            if($k >= $errorProvince){
                try {
                    echo PHP_EOL.$v.PHP_EOL;
                    $this->getCitys($v, $prefix.'/'.$hrefs[$k]);
                }catch (\Throwable $e){
                    file_put_contents($this->regionDir.'/error-province.log', $k); // 记录错误位置
                    echo '【发生错误】 '.date('Y-m-d H:i:s').' '.$e->getMessage().PHP_EOL;
                    die;
                }
            }
        }
    }

    /**
     * 城市
     * @param string $html
     * @return void
     */
    public function getCitys($provincialName , $url){

        $regionData = [];

        $prefix = dirname($url);
        $curl = new Curl();
        $html = $curl->request($url)['body'];

        $document = new Document($html);
        $allA = $document->getElementsByClassName('citytr')->getElementsByTagName('a');
        $allAData = $allA->toArray();
        foreach($allAData as $k => $v){
            if($k % 2 != 0){
                $cityCode = strip_tags($allAData[$k - 1]);
                $cityName = strip_tags($v);
                $childUrl = $prefix.'/'.(new Document($v))->getAttribute('href');
                $regionData[$cityCode] = [
                    'code' => $cityCode,
                    'name' => $cityName,
                ];
                echo $cityName.PHP_EOL;
                $this->getCounty($regionData[$cityCode], $childUrl);
            }
        }
        file_put_contents($this->regionDir.'/'.$provincialName.'.json', json_encode($regionData, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 区县
     * @param $regionData
     * @param $url
     * @return void
     */
    public function getCounty(&$regionData, $url){
        $prefix = dirname($url);
        $curl = new Curl();
        $html = $curl->request($url)['body'];

        $document = new Document($html);
        $allA = $document->getElementsByClassName('countytr')->getElementsByTagName('a');
        $allAData = $allA->toArray();
        foreach($allAData as $k => $v){
            if($k % 2 != 0){
                $countyCode = strip_tags($allAData[$k - 1]);
                $countyName = strip_tags($v);
                $childUrl = $prefix.'/'.(new Document($v))->getAttribute('href');
                $regionData[$countyCode] = [
                    'code' => $countyCode,
                    'name' => $countyName,
                ];
                $this->getTown($regionData[$countyCode], $childUrl);
            }
        }
    }

    /**
     * 街镇
     * @return void
     */
    public function getTown(&$regionData, $url){
        $prefix = dirname($url);
        $curl = new Curl();
        $html = $curl->request($url)['body'];

        $document = new Document($html);
        $allA = $document->getElementsByClassName('towntr')->getElementsByTagName('a');
        $allAData = $allA->toArray();
        foreach($allAData as $k => $v){
            if($k % 2 != 0){
                $townCode = strip_tags($allAData[$k - 1]);
                $townName = strip_tags($v);
                $childUrl = $prefix.'/'.(new Document($v))->getAttribute('href');
                $regionData[$townCode] = [
                    'code' => $townCode,
                    'name' => $townName,
                ];
                $this->getVillage($regionData[$townCode], $childUrl);
            }
        }
    }

    /**
     * 居村委
     * @return void
     */
    public function getVillage(&$regionData, $url){
        $prefix = dirname($url);
        $curl = new Curl();
        $html = $curl->request($url)['body'];

        $document = new Document($html);
        $allTd = $document->getElementsByClassName('villagetr')->getElementsByTagName('td');
        $allTdData = $allTd->toArray();
        foreach($allTdData as $k => $v){
            if(($k + 1) % 3 == 0){
                $villageCode = strip_tags($allTdData[$k - 2]);
                $villageName = strip_tags($v);
                $regionData[$villageCode] = [
                    'code' => $villageCode,
                    'name' => $villageName,
                ];
            }
        }
    }
}