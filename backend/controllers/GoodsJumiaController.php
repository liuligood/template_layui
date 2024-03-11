<?php

namespace backend\controllers;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\Goods;
use common\models\goods\GoodsJumia;
use common\models\goods\GoodsShopee;
use common\models\GoodsShop;
use common\services\goods\FGoodsService;
use common\services\goods\GoodsService;
use common\services\goods\platform\JumiaPlatform;
use Yii;

class GoodsJumiaController extends BaseGoodsController
{

    protected $render_view = '/goods/jumia/';

    protected $platform_type = Base::PLATFORM_JUMIA;

    protected $has_country = true;

    public function model(){
        return new GoodsJumia();
    }

    protected $export_column = [
        'goods_no' => '商品编号',
        'cgoods_no' => '子商品编号',
        'shop_name' => '店铺名称',
        'category_name' => '平台类目',
        'o_category_name' => 'Jumia类目',
        'platform_sku_no' => 'SKU',
        'ean' => 'EAN',
        'goods_name' => '标题',
        'goods_short_name' => '短标题',
        'goods_name_en' => '标题(EN)',
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
        //'colour1' => '颜色1',
        'size_l' => '尺寸(长)',
        'size_w' => '尺寸(宽)',
        'size_h' => '尺寸(高)',
        'weight' => '重量',
        'cjz_weight' => '材积重',
        'goods_content' => '详细描述',
        'goods_desc' => '简要描述',
        'goods_content_en' => '详细描述(EN)',
        'add_time' => '创建时间',
    ];

    public function query($type = 'select')
    {
        return $this->join_query('mg.*,mg.id as mg_id,g.category_id,g.sku_no,g.goods_img,gs.id,gs.shop_id,gs.original_price,gs.discount,gs.price,gs.admin_id,gs.add_time,mg.goods_content,mg.status,gs.ean,g.size,g.weight,g.colour as gcolour,g.goods_name as goods_name_en,g.goods_content as goods_content_en,g.goods_desc as goods_desc_en,gs.platform_sku_no,gs.cgoods_no',$type);
    }

    /**
     * 导出
     * @param $info
     * @return array
     */
    public function dealExport($info)
    {
        $p_class = FGoodsService::factory($this->platform_type);
        $info['goods_content_en'] = $p_class->dealContent([
            'goods_content' =>$info['goods_content_en'],
            'goods_name' =>$info['goods_name_en'],
            'goods_desc' =>$info['goods_desc_en'],
            'gcolour' => $info['gcolour']
        ]);
        return [
            'goods_name_en' => $info['goods_name_en'],
            'goods_content_en' => $info['goods_content_en'],
            /*'colour1' => !empty(JumiaPlatform::$colour_map[$info['gcolour']])?JumiaPlatform::$colour_map[$info['gcolour']]:JumiaPlatform::$colour_map['Black']*/
        ];
    }

}