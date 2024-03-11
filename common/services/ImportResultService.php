<?php
namespace common\services;

use common\components\CommonUtil;
use common\models\ImportResultModel;
use moonland\phpexcel\Excel;
use Yii;

class ImportResultService
{

    const DATA_CACHE_PERFIX = 'com:import_result_cache:';

    /**
     * 设置缓存
     * @param $key
     * @return string
     */
    public static function set($key,$val)
    {
        $cache_key = self::DATA_CACHE_PERFIX . $key;
        $data = Yii::$app->cache->set($cache_key,$val,60 * 10);
        return $data;
    }

    /**
     * 获取缓存
     * @param $key
     * @return string
     */
    public static function get($key)
    {
        $cache_key = self::DATA_CACHE_PERFIX . $key;
        $data = Yii::$app->cache->get($cache_key);
        return $data;
    }

    /**
     * 清除缓存
     * @param $key
     * @return mixed
     */
    public static function clear($key)
    {
        $cache_key = self::DATA_CACHE_PERFIX . $key;
        return Yii::$app->cache->del($cache_key);
    }

    /**
     * 生成key
     * @return string
     */
    public static function genKey(){
        return CommonUtil::randString(6);
    }


    /**
     * 生成excel数据
     * @param $file_name
     * @param $lists
     * @return string
     */
    public function gen($file_name,$lists)
    {
        $key = self::genKey();
        $data = [
            'file_name' => $file_name,
            'lists' => $lists
        ];
        self::set($key,$data);
        return $key;
    }

    /**
     * 生成excel
     * @param $key
     * @return bool|string
     */
    public function getExcel($key)
    {
        $data = self::get($key);
        if (empty($data)) {
            return false;
        }

        $file_name = empty($data['file_name']) ? '' : $data['file_name'];
        $lists = empty($data['lists']) ? [] : $data['lists'];

        if (!empty($lists)) {
            $export_data = [];
            foreach ($lists as $i => $row) {
                $model = new ImportResultModel();
                $model->index = empty($row['index'])?'':$row['index'];
                $model->rvalue1 = empty($row['rvalue1'])?'':$row['rvalue1'];
                $model->rvalue2 = empty($row['rvalue2'])?'':$row['rvalue2'];
                $model->rvalue3 = empty($row['rvalue3'])?'':$row['rvalue3'];
                $model->evalue = empty($row['evalue'])?'':$row['evalue'];
                $model->reason = empty($row['reason'])?'':$row['reason'];
                $export_data[] = $model->toArray();
            }

            /*return Excel::export([
                'fileName' => $file_name . '批量导入错误记录' . date('ymdhis'),
                'models' => $models,
                'columns' => ['index', 'rvalue1', 'rvalue2', 'rvalue3', 'evalue', 'reason'],
            ]);*/

            // 导出
            $export_column = (new ImportResultModel())->attributeLabels();
            $xlsservice = new XlsWriterService($file_name . '批量导入错误记录' . date('ymdhis'), $export_data,array_values($export_column));
            return $xlsservice->setCommonStyle()->setMultipleSheets(10000)->export();
        }
        return false;
    }

}