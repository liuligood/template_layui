<?php

namespace backend\controllers;

use common\components\statics\Base;
use common\models\goods\GoodsWalmart;

class GoodsWalmartController extends BaseGoodsController
{

    protected $render_view = '/goods/walmart/';

    protected $platform_type = Base::PLATFORM_WALMART;

    protected $has_country = true;

    public function model(){
        return new GoodsWalmart();
    }

    protected $export_column = [
        'goods_no' => '商品编号',
        'cgoods_no' => '子商品编号',
        'shop_name' => '店铺名称',
        'category_name' => '平台类目',
        'o_category_name' => 'Walmart类目',
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

}