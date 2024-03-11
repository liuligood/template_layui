<?php

namespace common\services;

use XLSXWriter;
use yii\base\Exception;
use common\components\CommonUtil;

/**
 * 订单流程标准化导出类
 */
class XlsWriterService
{
    public static $headerStyle = [
        'font' => '宋体',
        'font-size' => 12,
        'font-style' => 'bold',
        'fill' => '#eee',
        'halign' => 'center',
        'border' => 'left,right,top,bottom'
    ];

    public $writer = null;
    //文件名
    public $filename;
    //表头名
    public $header;
    //导出内容
    public $data;
    //列名
    public $title;
    //列样式
    public $column_style = [];
    //表头样式
    public $header_style;
    //单元格类型
    public $col_style;
    //sheet名
    public $sheet_name = 'sheet';
    //sheet数目
    public $sheet_num = 1;

    public function __construct($filename, $data, $title = [])
    {
        foreach ($data as &$v){
            $v = array_slice($v,0,count($title));
        }
        $this->writer = new XLSXWriter();
        if (empty($filename)) {
            throw new Exception('必要参数错误');
        } else {
            $this->filename = $filename;
            $this->data = $data;
            $this->title = $title;
        }
    }

    /*
     * 下载通用header头
     */
    public function commonExportHeader()
    {
        header("Content-Disposition:attachment;filename=" . $this->filename . '.xlsx');
        header('Content-Type: application/vnd.ms-excel;charset=utf-8');
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
    }

    /**
     * 通用样式
     */
    public function setCommonStyle($col_format_arr = [])
    {
        $col_format = [];
        $this->column_style = ['font' => '宋体', 'font-size' => 12, 'font-style' => 'bold', 'fill' => '#eee', 'halign'
        => 'center', 'border' => 'left,right,top,bottom', 'wrap_text' => true];
        $this->header_style = ['height' => 30, 'font-size' => 20, 'font-style' => 'bold', 'halign' => 'center', 'valign' => 'center', 'wrap_text' => true];
        if (empty($col_format_arr)) {
            foreach ($this->title as $v) {
                $col_style[] = 'string';
            }
        } else {
            foreach ($this->title as $k => $v) {
                if (isset($col_format_arr[$k])) {
                    $col_style[] = 'integer';
                } else {
                    $col_style[] = 'string';
                }
            }
        }
        $this->col_style = $col_style;
        return $this;
    }

    /**
     * 多sheet设置
     */
    public function setMultipleSheets($pernum)
    {
        $multiple_sheet_arr = array_chunk($this->data, intval($pernum));
        $sheetnum = count($multiple_sheet_arr);
        if ($sheetnum == 1) {
            $this->data = $multiple_sheet_arr[0];
        } else {
            $this->data = $multiple_sheet_arr;
        }
        $this->sheet_num = $sheetnum;
        return $this;
    }

    /**
     * 导出
     */
    public function export()
    {
        $this->writer->writeSheetRow('sheet1', $this->title, $this->column_style);
        $this->writer->writeSheetHeader('sheet1', $this->col_style, ['suppress_row' => true]);
        if ($this->sheet_num > 1) {
            foreach ($this->data as $k => $v) {
                foreach ($v as $key => $row) {
                    $this->writer->writeSheetRow('sheet' . ($k + 1), $row, ['height' => 18]);
                }
            }
        } else {
            foreach ($this->data as $row) {
                $this->writer->writeSheetRow('sheet1', $row, ['height' => 18]);
            }
        }
        $this->commonExportHeader();
        $this->writer->writeToStdOut();
        // 输出后一定要紧跟 exit 防止有其他输出导致 Excel 数据损坏，无法打开
        exit;
    }


    /**
     * 输出多个 sheet
     * 示例：
     * ```
     * $writer = new XlsWriterService($filename, $data, $title);// $title 未使用，传不为空的数组即可
     * $writer->writeMultiSheet()->download();// 输出 Excel 文件
     * ```
     *
     * 其中 `$data` 为多维数组，每一维分别是一个 sheet 的数据，格式为：[sheet名称, sheet标题 => 标题 format 格式数组, sheet数据]，如：
     * ```
     * $sheet1Header = [
     *    '总导入数' => 'integer',
     *    '导入成功数' => 'integer',
     *    '导入失败数' => 'integer'
     * ];
     * $sheet1Data = [
     *    [100, 80, 20],
     * ];
     * $data = [
     * ['导入结果概览', $sheet1Header, $sheet1Data],
     * ['导入成功', $sheet2Header, $sheet2Data],
     * ['导入失败', $sheet3Header, $sheet3Data],
     * ];
     */
    public function writeMultiSheet()
    {
        foreach ($this->data as $sheet) {
            list($name, $header, $data) = $sheet;
            $this->writeSheet($name, $header, $data);
        }

        return $this;
    }

    public function writeSingleSheet()
    {
        list($name, $header, $data) = $this->data;
        $this->writeSheet($name, $header, $data);

        return $this;
    }

    protected function writeSheet($name, $header, $data)
    {
        $this->writer->writeSheetRow($name, array_keys($header), self::$headerStyle);// 设置标题栏内容
        $this->writer->writeSheetHeader($name, array_values($header), ['suppress_row' => true]);// 设置标题栏 format 格式
        foreach($data as $row) {
            $this->writer->writeSheetRow($name, $row);
        }
    }

    public function download()
    {
        $this->commonExportHeader();
        $this->writer->writeToStdOut();
        exit;
    }

    /**
     * 获取单元格换行字符
     *
     * @return string
     */
    public static function getLineBreakChar()
    {
        // 根据当前操作系统类型来设置 Excel 表格换行符
        $clientOs = CommonUtil::getClientOs();
        $lineBreakChar = "&CHAR(10)&";// Windows
        if (false !== stripos($clientOs, 'mac')) {
            $lineBreakChar = "&CHAR(13)&";// MAC OS
        }

        return $lineBreakChar;
    }

    /**
     * 生成单元格换行字符串
     *
     * @param array $items 索引数组
     * @param string $lineBreakChar 先调用 XlsWriterService::getLineBreakChar() 获取，避免数据过大重复获取换行字符出现性能问题
     * @return string
     */
    public static function generateBreakLineCellStr($items, $lineBreakChar)
    {
        if (empty($items)) {
            return '';
        }
        $count = count($items);

        if (is_array($items)) {
            if (1 == $count) {
                return implode('', $items);
            } else {
                // 生成格式：'="ABC"&CHAR(10)&"DEF"'
                $str = '=';
                foreach ($items as $k => $item) {
                    $item = '"'. $item .'"';
                    if ($k == $count - 1) {
                        $str .= $item;
                    } else {
                        $str = $str . $item . $lineBreakChar;
                    }

                }

                return $str;
            }
        }

        return '';
    }
}
