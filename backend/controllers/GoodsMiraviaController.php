<?php


namespace backend\controllers;


use common\components\statics\Base;
use common\models\goods\GoodsMiravia;

class GoodsMiraviaController extends BaseGoodsController
{
    protected $render_view = '/goods/miravia/';

    protected $platform_type = Base::PLATFORM_MIRAVIA;

    public function model(){
        return new GoodsMiravia();
    }

    protected $export_column = [
        'goods_no' => '商品编号',
        'cgoods_no' => '子商品编号',
        'shop_name' => '店铺名称',
        'category_name' => '平台类目',
        'category_p_id' => '平台完整类目',
        'category_name_en' => '平台类目(EN)',
        'o_category_name' => 'Miravia类目',
        'platform_sku_no' => 'SKU',
        'ean' => 'EAN',
        'goods_name' => '标题',
        'goods_short_name' => '短标题',
        'image_all'=>'图片(总)',
        'price' => '价格',
        'colour' => '颜色',
        'size_l' => '长',
        'size_w' => '宽',
        'size_h' => '高',
        'real_weight' => '重量',
        'goods_content' => '详细描述',
        'goods_desc' => '简要描述',
        'add_time' => '创建时间',
    ];
}