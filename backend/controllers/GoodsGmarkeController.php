<?php

namespace backend\controllers;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\goods\GoodsGmarke;
use Yii;
use yii\web\Response;

class GoodsGmarkeController extends BaseGoodsController
{

    protected $render_view = '/goods/gmarke/';

    protected $platform_type = Base::PLATFORM_GMARKE;

    public function model(){
        return new GoodsGmarke();
    }

    protected $export_column = [
        'goods_no' => '商品编号',
        'cgoods_no' => '子商品编号',
        'shop_name' => '店铺名称',
        'category_name' => '平台类目',
        'o_category_name' => 'Gmarke类目',
        'platform_sku_no' => 'SKU',
        'ean' => 'EAN',
        'goods_name' => '标题',
        'goods_short_name' => '短标题',
        'image' => '主图',
        'all_image' => '副图',
        'price' => '价格',
        'brand' => '品牌',
        'colour' => '颜色',
        'size' => '尺寸',
        'weight' => '重量',
        'goods_content' => '详细描述',
        'goods_desc' => '简要描述',
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
        $image_arr = [];
        $image = json_decode($info['goods_img'], true);
        $i = 0;
        foreach ($image as $img_v){
            $i++;
            if(empty($img_v['img']) || $i > 5 || $i == 1){
                continue;
            }
            $image_arr[] = $img_v['img'];
        }
        $data['price'] = intval($info['price']);
        $data['all_image'] = implode(',',$image_arr);
        return $data;
    }

    /**
     * 导出
     * @param $info
     * @return array
     */
    public function afterDealExport($info)
    {
        $info['goods_content'] = '<p>모델:'.$info['platform_sku_no'].'</p>'.$info['goods_content'];
        return $info;
    }

}