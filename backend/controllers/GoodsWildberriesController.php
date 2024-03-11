<?php


namespace backend\controllers;


use common\components\statics\Base;
use common\models\Attachment;
use common\models\goods\GoodsImages;
use common\models\goods\GoodsWildberries;
use yii\helpers\ArrayHelper;

class GoodsWildberriesController extends BaseGoodsController
{
    protected $render_view = '/goods/wildberries/';

    protected $platform_type = Base::PLATFORM_WILDBERRIES;

    public function model(){
        return new GoodsWildberries();
    }

    protected $export_column = [
        'goods_no' => '商品编号',
        'cgoods_no' => '子商品编号',
        'shop_name' => '店铺名称',
        'category_name' => '平台类目',
        'category_p_id' => '平台完整类目',
        'category_name_en' => '平台类目(EN)',
        'o_category_name' => 'Wildberries类目',
        'platform_sku_no' => 'SKU',
        'ean' => 'EAN',
        'goods_name' => '标题',
        'goods_short_name' => '短标题',
        'all_image' => '图片',
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

    /**
     * 导出
     * @param $info
     * @return array
     */
    public function dealExport($info)
    {
        $data = [];
        $goods_image = GoodsImages::find()->alias('gi')
            ->select('path')
            ->leftJoin(Attachment::tableName().' at','at.id = gi.img_id')
            ->where(['platform_type' => $info['platform_type'],'goods_no' => $info['goods_no']])->asArray()->all();
        if (!empty($goods_image)) {
            $images = ArrayHelper::getColumn($goods_image,'path');
        } else {
            $images = json_decode($info['goods_img'],true);
            $images = ArrayHelper::getColumn($images,'img');
        }
        $images = implode('*',$images);
        $data['all_image'] = str_ireplace(' ','',$images);
        return $data;
    }

}