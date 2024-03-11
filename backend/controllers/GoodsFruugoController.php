<?php

namespace backend\controllers;

use common\components\statics\Base;
use common\models\goods\GoodsFruugo;

class GoodsFruugoController extends BaseGoodsController
{

    protected $render_view = '/goods/fruugo/';

    protected $platform_type = Base::PLATFORM_FRUUGO;

    protected $export_column = [
        'goods_no' => '商品编号',
        'cgoods_no' => '子商品编号',
        'shop_name' => '店铺名称',
        'category_name' => '平台类目',
        'o_category_name' => 'Fruugo类目',
        'platform_sku_no' => 'SKU',
        'price' => '价格',
        'ean' => 'EAN',
        'goods_name' => '标题',
        'goods_short_name' => '短标题',
        'image_all'=>'图片(总)',
        'brand' => '品牌',
        'colour' => '颜色',
        'size' => '尺寸',
        'weight' => '重量',
        'goods_content' => '详细描述',
        'goods_desc' => '简要描述',
        'add_time' => '创建时间',
    ];

    public function model(){
        return new GoodsFruugo();
    }

}