<?php

namespace backend\models\search;

use common\components\CommonUtil;
use common\models\Category;
use common\models\GoodsShop;
use common\models\Shop;
use common\models\Goods;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\FindGoods;

/**
 * FindGoodsSearch represents the model behind the search form of `common\models\FindGoods`.
 */
class FindGoodsSearch extends Goods
{

    public $shop_id;
    public $ean;
    public $platform_sku_no;
    public $un_o_category_name;
    public $start_add_time;
    public $end_add_time;
    public $country_code;
    public $un_goods_short_name;
    public $un_claim_shop_name;
    public $claim_shop_name;
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goods_no','goods_name','sku_no','platform_sku_no','shop_id','ean','un_o_category_name','un_goods_short_name','un_claim_shop_name','claim_shop_name','country_code'], 'string'],
            [['category_id','start_add_time','end_add_time','status'], 'integer'],
        ];
    }

    public function search($params,$tag,$platform_type)
    {
        $this->load($params);
        $where = [];

        $where['og.overseas_goods_status'] = $tag;

        $where['platform_type'] = $platform_type;

        if (!empty($this->goods_name)) {
            $goods_name = CommonUtil::searchWork($this->goods_name);
            $where['and'][] = "MATCH (goods_name) AGAINST ('".$goods_name."' IN BOOLEAN MODE)";
            //$where['and'][] = ['like', 'goods_name', $this->goods_name];
        }

        if (!empty($this->goods_no)) {
            $goods_no = explode(PHP_EOL,$this->goods_no);
            foreach ($goods_no as &$v){
                $v = trim($v);
            }
            $goods_no = array_filter($goods_no);
            $goods_no = count($goods_no) == 1?current($goods_no):$goods_no;
            $where['og.goods_no'] = $goods_no;
        }

        if (!empty($this->id)) {
            $where['id'] = $this->id;
        }

        if (!empty($this->sku_no)) {
            $sku_no = explode(PHP_EOL,$this->sku_no);
            foreach ($sku_no as &$v){
                $v = trim($v);
            }
            $sku_no = array_filter($sku_no);
            $sku_no = count($sku_no) == 1?current($sku_no):$sku_no;
            $where['sku_no'] = $sku_no;
        }

        if (!empty($this->status)) {
            $where['and'][] = ['=','status',$this->status];
        }

        if (!empty($this->category_id)) {
            if ($this->category_id == -1) {
                $category = 0;
            }else{
                $category = Category::collectionChildrenId($this->category_id);
                $category[] = $this->category_id;
            }
            $where['category_id'] = $category;
        }

        //已认领店铺
        if(!empty($this->claim_shop_name)){
            //$shop_id = Shop::find()->where(['name'=>$this->claim_shop_name])->select('id')->scalar();
            $shop_id = $this->claim_shop_name;
            $where['and'][] = ['in','og.goods_no',GoodsShop::find()->select('goods_no')->where(['shop_id'=>$shop_id])];
        }

        //未认领店铺
        if(!empty($this->un_claim_shop_name)){
            $this->un_claim_shop_name = array_filter($this->un_claim_shop_name);
            if(!empty($this->un_claim_shop_name)) {
                $platform = [];
                $shop_id = [];
                foreach ($this->un_claim_shop_name as $un_claim_shop_v){
                    if(strpos($un_claim_shop_v,'P_') !== false){
                        $platform[] = str_replace('P_','',$un_claim_shop_v);
                    }else {
                        $shop_id[] = $un_claim_shop_v;
                    }
                }
                if($platform) {
                    $platform_shop_id = Shop::find()->where(['platform_type' => $platform])->select('id')->column();
                    $shop_id = array_merge($shop_id,$platform_shop_id);
                    $shop_id = array_filter($shop_id);
                }
                $where['and'][] = ['not in', 'og.goods_no', GoodsShop::find()->select('goods_no')->where(['shop_id' => $shop_id])];
            }
        }

        //时间
        if (!empty($this->start_add_time)) {
            $where['and'][] = ['>=', 'og.add_time', strtotime($this->start_add_time)];
        }
        if (!empty($this->end_add_time)) {
            $where['and'][] = ['<=', 'og.add_time', strtotime($this->end_add_time)];
        }

        return $where;
    }
}
