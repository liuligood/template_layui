<?php

namespace backend\controllers;

use common\components\statics\Base;
use common\models\goods\GoodsWorten;
use common\services\goods\GoodsTranslateService;
use common\services\goods\platform\WortenPlatform;
use Yii;

class GoodsWortenController extends BaseGoodsController
{

    protected $render_view = '/goods/worten/';

    protected $platform_type = Base::PLATFORM_WORTEN;

    public function model(){
        return new GoodsWorten();
    }

    protected $export_column = [
        'goods_no' => '商品编号',
        'cgoods_no' => '子商品编号',
        'shop_name' => '店铺名称',
        'category_name' => '平台类目',
        'o_category_name' => 'Worten类目',
        'platform_sku_no' => 'SKU',
        'ean' => 'EAN',
        'goods_name' => '标题(PT)',
        'goods_name_es' => '标题(ES)',
        'goods_short_name' => '短标题',
        'image' => '图片1',
        'image2' => '图片2',
        'image3' => '图片3',
        'image4' => '图片4',
        'image5' => '图片5',
        'price' => '价格(PT)',
        'price_es' => '价格(ES)',
        'brand' => '品牌',
        'colour' => '颜色',
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
        $goods_translate_service = new GoodsTranslateService('es');
        //已经翻译的数据
        $goods_translate_info = $goods_translate_service->getGoodsInfo($info['goods_no'],['goods_name']);
        $data['colour'] = empty(WortenPlatform::$colour_map[$info['colour']])?'Preto':WortenPlatform::$colour_map[$info['colour']];
        $data['goods_name_es'] = !empty($goods_translate_info['goods_name'])?$goods_translate_info['goods_name']:'';
        $data['price_es'] = round($info['price']/1.23*1.21,2);
        return$data;
    }

}