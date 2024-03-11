<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\GoodsPriceTrial;


class GoodsPriceTrialSearch extends GoodsPriceTrial
{
    public $goods_no;
    public $sku_no;


    public function rules()
    {
        return [
            [['id', 'platform_type', 'add_time', 'update_time'], 'integer'],
            [['cgoods_no','goods_no','sku_no'], 'string'],
            [['price'], 'number'],
        ];
    }

    public function search($params, $platform_type)
    {
        $where = [];

        $this->load($params);

        $where['gpt.platform_type'] = $platform_type;

        if (!empty($this->goods_no)) {
            $goods_no = explode(PHP_EOL,$this->goods_no);
            foreach ($goods_no as &$v){
                $v = trim($v);
            }
            $goods_no = array_filter($goods_no);
            $goods_no = count($goods_no) == 1?current($goods_no):$goods_no;
            $where['gc.goods_no'] = $goods_no;
        }

        if (!empty($this->sku_no)) {
            $sku_no = explode(PHP_EOL,$this->sku_no);
            foreach ($sku_no as &$v){
                $v = trim($v);
            }
            $sku_no = array_filter($sku_no);
            $sku_no = count($sku_no) == 1?current($sku_no):$sku_no;
            $where['gc.sku_no'] = $sku_no;
        }

        return $where;
    }
}
