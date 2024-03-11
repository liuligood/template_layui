<?php

namespace backend\controllers;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\goods\GoodsMercado;
use common\services\goods\platform\MercadoPlatform;
use Yii;
use yii\web\Response;

class GoodsMercadoController extends BaseGoodsController
{

    protected $render_view = '/goods/mercado/';

    protected $platform_type = Base::PLATFORM_MERCADO;

    protected $has_country = true;

    public function model(){
        return new GoodsMercado();
    }

    protected $export_column = [
        'goods_no' => '商品编号',
        'cgoods_no' => '子商品编号',
        'shop_name' => '店铺名称',
        //'category_name' => '平台类目',
        //'o_category_name' => 'Mercado类目',
        'platform_sku_no' => 'SKU',
        'price' => '价格',
        /*'ean' => 'EAN',
        'goods_name' => '标题',
        'goods_short_name' => '短标题',
        //'image' => '主图',
        'all_image' => '图片(总)',
        'brand' => '品牌',
        'colour' => '颜色',
        'size' => '尺寸',
        'weight' => '重量',
        'goods_content' => '详细描述',
        'goods_desc' => '简要描述',
        'add_time' => '创建时间',*/
    ];

}