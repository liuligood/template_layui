<?php

namespace backend\controllers;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\goods\GoodsRdc;
use common\services\goods\platform\RdcPlatform;
use Yii;

class GoodsRdcController extends BaseGoodsController
{

    protected $render_view = '/goods/rdc/';

    protected $platform_type = Base::PLATFORM_RDC;

    public function model(){
        return new GoodsRdc();
    }

    protected $export_column = [
        'goods_no' => '商品编号',
        'cgoods_no' => '子商品编号',
        'shop_name' => '店铺名称',
        'category_name' => '平台类目',
        'o_category_name' => 'RDC类目',
        'sku_no' => 'SKU',
        'platform_sku_no' => '自定义SKU',
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
        'price' => '价格',
        'brand' => '品牌',
        'colour' => '颜色',
        'size' => '尺寸',
        'weight' => '重量',
        'goods_content' => '详细描述',
        'goods_desc' => '简要描述',
        'add_time' => '创建时间',
    ];

}