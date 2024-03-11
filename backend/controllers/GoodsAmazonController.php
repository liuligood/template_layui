<?php

namespace backend\controllers;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\goods\GoodsAmazon;
use common\models\GoodsShop;
use common\services\goods\GoodsService;
use common\services\goods\GoodsShopService;
use common\services\ImportResultService;
use common\services\ShopService;
use moonland\phpexcel\Excel;
use Yii;
use yii\web\Response;
use yii\web\UploadedFile;

class GoodsAmazonController extends BaseGoodsController
{

    protected $render_view = '/goods/amazon/';

    protected $platform_type = Base::PLATFORM_AMAZON;

    protected $has_country = true;

    protected $max_num = 150;

    public function model(){
        return new GoodsAmazon();
    }

    /**
     * @routeName 重新生成ena
     * @routeDescription 重新生成ena
     * @return array
     * @throws
     */
    public function actionBatchRegenerateEna()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->post('id');
        $goods_shop = GoodsShop::find()->where(['id' => $id])->all();
        if (empty($goods_shop)) {
            return $this->FormatArray(self::REQUEST_FAIL, "商品不能为空", []);
        }
        foreach ($goods_shop as $goods_model) {
            $ean = '';
            if (empty($ean)) {
                while (true) {
                    $ean = CommonUtil::GenerateEan13(9);
                    $exist_ean = GoodsShop::find()->where(['ean' => $ean, 'platform_type' => $this->platform_type])->exists();
                    if (!$exist_ean) {
                        break;
                    }
                }
            }
            $goods_model->ean = $ean;
            $goods_model->save();
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "生成成功", []);
    }

    protected $export_column = [
        'goods_no' => '商品编号',
        'cgoods_no' => '子商品编号',
        'shop_name' => '店铺名称',
        'country' => '国家',
        'category_name' => '平台类目',
        'o_category_name' => 'Amazon类目',
        'platform_sku_no' => 'SKU',
        'platform_goods_id' => 'ASIN',
        'ean' => 'EAN',
        'goods_name' => '标题',
        'goods_short_name' => '短标题',
        'image' => '图片1',
        'image2' => '图片2',
        'image3' => '图片3',
        'image4' => '图片4',
        'image5' => '图片5',
        'image6' => '图片6',
        'image7' => '图片7',
        'image8' => '图片8',
        'image9' => '图片9',
        'goods_desc1' => '要素1',
        'goods_desc2' => '要素2',
        'goods_desc3' => '要素3',
        'goods_desc4' => '要素4',
        'goods_desc5' => '要素5',
        'price' => '价格',
        'brand' => '品牌',
        'colour' => '颜色',
        'size' => '尺寸',
        'weight' => '重量',
        'cjz_weight' => '材积重',
        'goods_content' => '详细描述',
        'add_time' => '创建时间',
    ];

    /**
     * 导出
     * @param $info
     * @return array
     */
    public function dealExport($info)
    {
        $data = [];
        $image = json_decode($info['goods_img'], true);
        $data['image'] = !empty($image[0])?($image[0]['img'].'?imageMogr2/thumbnail/!1000x1000r'):'';
        return$data;
    }

    public function actionImportAsin()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $file = UploadedFile::getInstanceByName('file');
        if (!in_array($file->extension, ['xlsx', 'xls'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "只允许使用以下文件扩展名的文件：xlsx, xls。", []);
        }

        // 读取excel文件
        $data = Excel::import($file->tempName, [
            'setFirstRecordAsKeys' => false,
        ]);

        // 多Sheet
        if (isset($data[0])) {
            $data = $data[0];
        }

        $rowKeyTitles = [
            'shop_name' => '店铺名称',
            //'country' => '国家',
            'sku_no' => 'SKU',
            'platform_goods_id' => 'ASIN',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }
        if(empty($keyMap['shop_name']) || empty($keyMap['sku_no'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }

        $count = count($data);
        $success = 0;
        $errors = [];
        $shop_map=ShopService::getShopMapId();
        for ($i = 2; $i <= $count; $i++) {
            $row = $data[$i];
            foreach ($row as &$rowValue) {
                $rowValue = !empty($rowValue) ? str_replace(' ', '', $rowValue) : '';
            }

            foreach (array_keys($rowKeyTitles) as $rowMapKey) {
                $rowKey = isset($keyMap[$rowMapKey]) ? $keyMap[$rowMapKey] : '';
                $$rowMapKey = isset($row[$rowKey]) ? $row[$rowKey] : '';
            }

            if (empty($platform_goods_id)) {
                $errors[$i] = 'ASIN';
                continue;
            }

            try {
                //$country=(explode('(',$country))[1];
                //$country=substr($country,0,2);
                $orders = GoodsShop::find()->where(['platform_sku_no'=>$sku_no,'shop_id'=>$shop_map[$shop_name],'platform_type'=>$this->platform_type])->all();
                if(empty($orders)){
                    $errors[$i] = '该商品对应店铺不存在';
                    continue;
                }
                foreach ($orders as $order) {
                    $order->platform_goods_id = empty($platform_goods_id) ? '' : $platform_goods_id;
                    $order->save();
                }
            }catch (\Exception $e) {
                $errors[$i] = $e->getMessage();
                continue;
            }

            $success++;
        }

        if(!empty($errors)) {
            $lists = [];
            foreach ($errors as $i => $error) {
                $row = $data[$i];
                $info = [];
                $info['index'] = $i;
                $info['rvalue1'] = $row[$keyMap['shop_name']];
                $info['rvalue2'] = $row[$keyMap['sku_no']];
                $info['rvalue3'] = $row[$keyMap['platform_goods_id']];
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('导入Asin', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }

        return $this->FormatArray(self::REQUEST_SUCCESS, "导入成功", []);
    }



}