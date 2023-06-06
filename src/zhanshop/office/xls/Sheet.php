<?php
// +----------------------------------------------------------------------
// | youyao-v3 / Sheet.php    [ 2023/6/2 下午11:00 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\office\xls;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use zhanshop\App;

class Sheet
{
    protected $columnKey = [];
    protected Worksheet $worksheet;
    protected $data = [];
    protected $headNum = 0;

    public function __construct(Worksheet &$worksheet)
    {
        $this->worksheet = $worksheet;

        $factor = ['A', 'B', 'C', 'D', 'E', 'F','G', 'H', 'I','J', 'K', 'L','M', 'N', 'O','P', 'Q', 'R','S', 'T', 'U','V', 'W', 'X','Y', 'Z'];
        for($i = 0; $i < 640; $i ++){
            $c = (floor($i / 26) - 1) % 26;
            $e = $i % 26;
            $this->columnKey[] = ($factor[$c] ?? "") . $factor[$e];
        }
    }

    /**
     * 设置表头
     * @param string $title
     * @param int $width
     * @param bool $isBold
     * @param string $format
     * @param bool $isCenter
     * @param string $bgColor
     * @param bool $isBorder
     * @return void
     */
    public function setHead(string $title, int $width = 10, bool $isBold = true, string $format = 'General', bool $isCenter = true, string $bgColor = 'FFFFFFFF'): Sheet{
        $this->data[0][] = [
            'width' => $width,
            'isBold' => $isBold,
            'format' => $format,
            'isCenter' => $isCenter,
            'bgColor' => $bgColor,
            'val' => $title,
        ];
        return $this;
    }

    /**
     * 设置内容
     * @param int $key
     * @param mixed $value
     * @param bool $isBold
     * @param string $format
     * @param bool $isCenter
     * @param string $bgColor
     * @return $this
     * @throws \Exception
     */
    public function setBody(int $key, mixed $value, bool $isBold = false, string $bgColor = 'FFFFFFFF', string $format = 'General', bool $isCenter = true) :Sheet{
        if($this->headNum == false){
            if($this->data == false){
                App::error()->setError('表头还没有设定');
            }else{
                $this->headNum = count($this->data[0]);
            }
        }

        $this->data[$key + 1][] = [
            'isBold' => $isBold,
            'format' => $format,
            'isCenter' => $isCenter,
            'bgColor' => $bgColor,
            'val' => $value,
        ];

        return $this;
    }

    public function write(){
        foreach ($this->data as $k => $v){
            foreach ($v as $sKey => $options){
                $sKey = $this->columnKey[$sKey].($k + 1);
                $pDataType = DataType::TYPE_STRING;

                $sItem = $options['val'];
                /* 设置单元格格式 */
                if (isset($options['format']) && !empty($options['format'])) {
                    $colRow = Coordinate::coordinateFromString($sKey);

                    /* 存在该列格式并且有特殊格式 */
                    if (isset($options['format'][$colRow[0]]) &&
                        NumberFormat::FORMAT_GENERAL != $options['format'][$colRow[0]]) {
                        $this->worksheet->getStyle($sKey)->getNumberFormat()
                            ->setFormatCode($options['format'][$colRow[0]]);

                        if (false !== strpos($options['format'][$colRow[0]], '0.00') &&
                            is_numeric(str_replace(['￥', ','], '', $sItem))) {
                            /* 数字格式转换为数字单元格 */
                            $pDataType = DataType::TYPE_NUMERIC;
                            $sItem     = str_replace(['￥', ','], '', $sItem);
                        }
                    } elseif (is_int($sItem)) {
                        $pDataType = DataType::TYPE_NUMERIC;
                    }
                }
                $this->worksheet->setCellValueExplicit($sKey, $sItem, $pDataType);
            }

            /* 设置宽度 */
            foreach ($this->data[0] as $swKey => $options) {
                $swKey = $this->columnKey[$swKey];
                $this->worksheet->getColumnDimension($swKey)->setWidth($options['width']);
            }

        }
    }
}