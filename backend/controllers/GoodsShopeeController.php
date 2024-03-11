<?php

namespace backend\controllers;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\goods\GoodsShopee;
use common\services\goods\GoodsService;
use Yii;

class GoodsShopeeController extends BaseGoodsController
{

    protected $render_view = '/goods/shopee/';

    protected $platform_type = Base::PLATFORM_SHOPEE;

    protected $has_country = true;

    public function model(){
        return new GoodsShopee();
    }

    protected $export_column = [
        'goods_no' => '商品编号',
        'cgoods_no' => '子商品编号',
        'shop_name' => '店铺名称',
        'category_name' => '平台类目',
        'o_category_name' => 'Shopee类目',
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
        'cjz_weight' => '材积重',
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
        $image = $info['goods_img'];
        $watemark = '';
        if($info['shop_id'] == 39) {
            $logo = 'a29kbzovL2J5c2hvcC9sb2dvL3NhbmxpYmVhbnMucG5n';
        }
        if($info['shop_id'] == 92){
            $logo = 'a29kbzovL2J5c2hvcC9sb2dvL3lqZHMucG5n';
        }
        if(!empty($logo)) {
            $watemark = '?watermark/1/image/' . $logo . '/dissolve/100/gravity/NorthWest/dx/9/dy/-20/ws/0.25';
        }

        $data['image'] = !empty($image[0])?$image[0]['img'].$watemark:'';
        $data['image2'] = !empty($image[1])?$image[1]['img'].$watemark:'';
        $data['image3'] = !empty($image[2])?$image[2]['img'].$watemark:'';
        $data['image4'] = !empty($image[3])?$image[3]['img'].$watemark:'';
        $data['image5'] = !empty($image[4])?$image[4]['img'].$watemark:'';
        $data['image6'] = !empty($image[5])?$image[5]['img'].$watemark:'';
        $data['image7'] = !empty($image[6])?$image[6]['img'].$watemark:'';
        $data['weight'] = $info['weight']*1.3;
        return$data;
    }

}