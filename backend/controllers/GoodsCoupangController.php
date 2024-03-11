<?php

namespace backend\controllers;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\goods\GoodsCoupang;
use common\services\goods\platform\CoupangPlatform;
use Yii;
use yii\web\Response;

class GoodsCoupangController extends BaseGoodsController
{

    protected $render_view = '/goods/coupang/';

    protected $platform_type = Base::PLATFORM_COUPANG;

    protected $export_column = [
        'goods_no' => '商品编号',
        'cgoods_no' => '子商品编号',
        'shop_name' => '店铺名称',
        'category_name' => '平台类目',
        'o_category_name' => 'Coupang类目',
        'platform_sku_no' => 'SKU',
        'ean' => 'EAN',
        'goods_name' => '标题',
        'goods_short_name' => '短标题',
        'image' => '图片1',
        'image2' => '图片2',
        'image3' => '图片3',
        'image4' => '图片4',
        'image5' => '图片5',
        'price' => '价格',
        'brand' => '品牌',
        'colour' => '颜色',
        'size' => '尺寸',
        'weight' => '重量',
        'goods_content' => '详细描述',
        'goods_desc' => '简要描述',
        'add_time' => '创建时间',
    ];

    public function model(){
        return new GoodsCoupang();
    }

    /**
     * 导出
     * @param $info
     * @return array
     */
    public function dealExport($info)
    {
        $goods_name = str_replace('|',' ',$info['goods_name']);
        $goods_name = strpos($goods_name, '(') !== false || strpos($goods_name, '（') !== false ? $goods_name:($goods_name.'('.$info['colour'].')');
        $goods_short_name = str_replace('|',' ',$info['goods_short_name']);
        $goods_short_name = strpos($goods_short_name, '(') !== false || strpos($goods_short_name, '（') !== false ? $goods_short_name:($goods_short_name.'('.$info['colour'].')');
        return [
            'goods_name' => $goods_name,
            'goods_short_name' => $goods_short_name,
        ];
    }

}