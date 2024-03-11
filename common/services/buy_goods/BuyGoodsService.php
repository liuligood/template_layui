<?php
namespace common\services\buy_goods;

use common\components\statics\Base;
use common\models\BuyGoods;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\GoodsSource;
use common\services\FGrabService;
use common\services\goods\GoodsService;
use Yii;
use yii\helpers\ArrayHelper;

class BuyGoodsService
{

    /**
     * 添加商品
     * @param $order_goods
     * @throws \yii\base\Exception
     */
    public function addGoods($order_goods)
    {
        if(empty($order_goods['has_buy_goods'])){
            return;
        }

        if(empty($order_goods['platform_asin']) || empty($order_goods['platform_type'])){
            return ;
        }
        $asin = $order_goods['platform_asin'];
        $goods = $this->getGoods($order_goods['platform_asin'],$order_goods['source_method'],$order_goods['platform_type']);
        if(empty($order_goods['goods_pic'])) {
            $buy_goods_pic = empty($goods['buy_goods_pic']) ? '' : $goods['buy_goods_pic'];
        } else {
            $buy_goods_pic = $order_goods['goods_pic'];
        }
        $data = [
            'order_id' => $order_goods['order_id'],
            'order_goods_id' => $order_goods['order_goods_id'],
            'platform_type' => $order_goods['platform_type'],
            'source_method' => $order_goods['source_method'],
            'asin' => $asin,
            'buy_goods_num' => $order_goods['goods_num'],
            'buy_goods_url' => empty($goods['buy_goods_url'])?'':$goods['buy_goods_url'],
            'buy_goods_pic' => $buy_goods_pic,
            'buy_goods_price' => empty($order_goods['goods_cost_price'])?0:$order_goods['goods_cost_price'],
            'buy_goods_status' => BuyGoods::BUY_GOODS_STATUS_NONE,
        ];
        if($order_goods['out_stock'] == 1){
            $data['buy_goods_status'] = BuyGoods::BUY_GOODS_STATUS_OUT_STOCK;
        }
        if($order_goods['error_con'] == 1){
            $data['buy_goods_status'] = BuyGoods::BUY_GOODS_STATUS_ERROR_CON;
        }
        BuyGoods::add($data);
    }

    /**
     * 更新商品
     * @param $order_goods
     * @throws \yii\base\Exception
     */
    public function updateGoods($order_goods)
    {
        $buy_goods = BuyGoods::findOne(['order_goods_id'=>$order_goods['order_goods_id']]);
        if(empty($buy_goods)){
            $this->addGoods($order_goods);
        } else {
            $buy_goods['source_method'] = $order_goods['source_method'];
            $buy_goods['platform_type'] = $order_goods['platform_type'];
            if(!in_array($buy_goods['buy_goods_status'],[BuyGoods::BUY_GOODS_STATUS_BUY,BuyGoods::BUY_GOODS_STATUS_DELIVERY,BuyGoods::BUY_GOODS_STATUS_FINISH])) {
                if ($buy_goods['asin'] != $order_goods['platform_asin']) {
                    $buy_goods['asin'] = $order_goods['platform_asin'];
                    $goods = $this->getGoods($order_goods['platform_asin'],$order_goods['source_method'],$order_goods['platform_type']);
                    $buy_goods['buy_goods_url'] = empty($goods['buy_goods_url'])?'':$goods['buy_goods_url'];
                    $buy_goods['buy_goods_pic'] = empty($goods['buy_goods_pic'])?'':$goods['buy_goods_pic'];
                }
                if ($buy_goods['buy_goods_num'] != $order_goods['goods_num']) {
                    $buy_goods['buy_goods_num'] = $order_goods['goods_num'];
                }
                if ($buy_goods['buy_goods_price'] != $order_goods['goods_cost_price']) {
                    $buy_goods['buy_goods_price'] = $order_goods['goods_cost_price'];
                }
                if($order_goods['out_stock'] == 1){
                    $buy_goods['buy_goods_status'] = BuyGoods::BUY_GOODS_STATUS_OUT_STOCK;
                }
                if($order_goods['error_con'] == 1){
                    $buy_goods['buy_goods_status'] = BuyGoods::BUY_GOODS_STATUS_ERROR_CON;
                }
                $buy_goods->save();
            }
        }
    }

    /**
     * 删除商品
     * @param $order_goods_ids
     */
    public function deleteGoods($order_goods_ids)
    {
        BuyGoods::updateAll(['buy_goods_status'=>BuyGoods::BUY_GOODS_STATUS_DELETE],['order_goods_id'=>$order_goods_ids,'buy_goods_status'=>[BuyGoods::BUY_GOODS_STATUS_NONE,BuyGoods::BUY_GOODS_STATUS_OUT_STOCK]]);
    }


    /**
     * 获取链接
     * @param $goods
     * @return string
     */
    public static function getUrl($goods)
    {
        $url = '';
        if($goods['source_method'] == GoodsService::SOURCE_METHOD_AMAZON){
            $domain = FGrabService::$source_map[$goods['source_platform_type']]['domain'];
            $url = 'https://'.$domain.'/dp/' . $goods['sku_no'];
        } else {
            $goods_source = GoodsSource::find()->where(['goods_no'=>$goods['goods_no'],'platform_type'=>Base::PLATFORM_1688])->one();
            $url = empty($goods_source['platform_url'])?'':$goods_source['platform_url'];
        }
        return $url;
    }

    /**
     * 获取商品信息
     * @param $sku_no
     * @param string $country
     * @param int $default_platform_type 默认平台类型
     * @return array
     */
    public function getGoodsToSkuCountry($sku_no,$country = '',$default_platform_type = Base::PLATFORM_AMAZON_DE,$goods_type = 1)
    {
        $country_map = [
            'DE' => Base::PLATFORM_AMAZON_DE,
            'GB' => Base::PLATFORM_AMAZON_CO_UK,
            'United Kingdom' => Base::PLATFORM_AMAZON_CO_UK,
            'IT' => Base::PLATFORM_AMAZON_IT,
        ];
        if($goods_type == 2){
            $where = [
                'cgoods_no' => $sku_no
            ];
        }else {
            $where = [
                'sku_no' => $sku_no
            ];
        }
        $goods_child = GoodsChild::find()->where($where)->select('goods_no,cgoods_no,sku_no,goods_img')->indexBy('goods_no')->all();
        $goods_no = ArrayHelper::getColumn($goods_child,'goods_no');
        $goods_lists = Goods::find()->where(['goods_no'=>$goods_no])->asArray()->all();
        if (empty($goods_lists)) {//sku不存在默认为亚马逊
            if($default_platform_type == Base::PLATFORM_1688){
                return [
                    'source_method' => GoodsService::SOURCE_METHOD_OWN,
                    'platform_type' => $default_platform_type,
                    'platform_sku_no' => $sku_no,
                    'goods_no' => '',
                    'cgoods_no' => '',
                ];
            }
            return [
                'source_method' => GoodsService::SOURCE_METHOD_AMAZON,
                'platform_type' => empty($country_map[$country]) ? $default_platform_type : $country_map[$country],
                'platform_sku_no' => $sku_no,
                'goods_no' => '',
                'cgoods_no' => '',
            ];
        } else {
            $info = [];
            foreach ($goods_lists as $goods) {
                $goods_child_info = empty($goods_child[$goods['goods_no']])?[]:$goods_child[$goods['goods_no']];
                $info['platform_sku_no'] = empty($goods_child_info['sku_no'])?$sku_no:$goods_child_info['sku_no'];
                $info['source_method'] = $goods['source_method'];
                $info['goods_no'] = $goods['goods_no'];
                $info['cgoods_no'] = empty($goods_child_info['cgoods_no'])?'':$goods_child_info['cgoods_no'];
                if(!empty($goods_child_info['goods_img'])) {
                    $image_info = $goods_child_info['goods_img'];
                } else {
                    $image = json_decode($goods['goods_img'], true);
                    $image_info = empty($image) || !is_array($image) ? '' : current($image)['img'];
                }
                if ($goods['source_method'] == GoodsService::SOURCE_METHOD_OWN) {
                    $info['buy_goods_pic'] = $image_info;
                    $info['goods_name'] = $goods['goods_name'];
                    //$info['buy_goods_url'] = self::getUrl($goods);
                    $info['platform_type'] = Base::PLATFORM_1688;
                    break;
                } else {
                    $info['platform_type'] = empty($country_map[$country]) ? $default_platform_type : $country_map[$country];
                    if ($info['platform_type'] == $goods['source_platform_type']) {
                        $info['buy_goods_pic'] = $image_info;
                        //$info['buy_goods_url'] = self::getUrl($goods);
                        break;
                    }
                }
            }
            return $info;
        }
    }

    /**
     * 获取商品
     * @param $sku_no
     * @param $source_method
     * @param $platform_type
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getGoods($sku_no,$source_method,$platform_type)
    {
        $where = [
            'sku_no'=>$sku_no,
            'source_method'=>$source_method
        ];
        if($source_method == GoodsService::SOURCE_METHOD_AMAZON){
            $where['source_platform_type'] = $platform_type;
        }
        $goods = Goods::find()->where($where)->asArray()->one();
        if(empty($goods)){
            return [];
        }
        $goods['buy_goods_url'] = self::getUrl($goods);
        $image = json_decode($goods['goods_img'], true);
        $goods['buy_goods_pic'] = empty($image) || !is_array($image) ? '' : current($image)['img'];
        return $goods;
    }

}