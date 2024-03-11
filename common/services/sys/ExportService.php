<?php
/**
 * Created by PhpStorm.
 * User: chenweihao
 * Date: 2022/6/22
 * Time: 10:45
 */
namespace common\services\sys;

class ExportService
{

    public $max_num = 100000;//限制导出条数
    public $page_size = 100;//导出每页行数
    public $file_page = 100;//多少页进行分文件导出

    public function __construct($page_size = 0,$max_nums = 0,$file_page = 0)
    {
        if (!empty($page_size)) {
            $this->page_size = $page_size;
        }

        if (!empty($max_nums)) {
            $this->max_num = $max_nums;
        }

        if (!empty($file_page)) {
            $this->file_page = $file_page;
        }
    }

    /**
     * 导出数据
     * @param $column
     * @param $data
     * @param $file_name
     * @return array
     */
    public function forData($column,$data,$file_name)
    {
        return [
            'key' => array_keys($column),
            'header' => array_values($column),
            'list' => $data,
            'fileName' => $file_name
        ];
    }

    /**
     * 导出js格式化基本参数
     * @param int $total_count 总数
     * @param int $page_size 每页条数
     * @param int $file_page 多少页分文件
     * @param int $max_num 最大条数
     * @return array
     */
    public function forHeadConfig($total_count, $page_size = 0, $max_num = 0 ,$file_page = 0)
    {
        if (empty($file_page)) {
            $file_page = $this->file_page;
        }

        if (empty($max_num)) {
            $max_num = $this->max_num;
        }

        if (empty($page_size)) {
            $page_size = $this->page_size;
        }

        $total_page = ceil($total_count / $page_size);

        $return = [
            'total_page' => $total_page,//总页数
            'file_page' => (int)$file_page,//多少页进行分文件导出 例如总页数100页 file_page为50页那么将导出excel两个文件
            'total_count' => (int)$total_count,//总数 用于检测是否大于允许导出最大值
            'max_export_num' => (int)$max_num,//允许导出最大值
        ];
        return $return;
    }

}