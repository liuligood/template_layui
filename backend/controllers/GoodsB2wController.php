<?php

namespace backend\controllers;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\goods\GoodsB2w;
use common\services\goods\GoodsService;
use common\services\goods\platform\B2wPlatform;
use Yii;

class GoodsB2wController extends BaseGoodsController
{

    protected $render_view = '/goods/b2w/';

    protected $platform_type = Base::PLATFORM_B2W;

    public function model(){
        return new GoodsB2w();
    }

    protected $export_column = [
        'goods_no' => '商品编号',
        'cgoods_no' => '子商品编号',
        'shop_name' => '店铺名称',
        'category_name' => '平台类目',
        'o_category_name' => 'B2w类目',
        'platform_sku_no' => 'SKU',
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
        'size_l' => '尺寸(长)',
        'size_w' => '尺寸(宽)',
        'size_h' => '尺寸(高)',
        'weight' => '重量',
        'goods_content' => '详细描述',
        'goods_desc' => '简要描述',
        'add_time' => '创建时间',
    ];

}