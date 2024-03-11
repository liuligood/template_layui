<?php
namespace backend\models\search;

use common\models\Category;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsStock;
use common\models\goods\GoodsStockDetails;
use common\models\goods_shop\GoodsShopFollowSale;
use common\models\goods_shop\GoodsShopOverseasWarehouse;
use common\models\goods_shop\GoodsShopSalesTotal;
use common\models\GoodsShop;
use common\models\Shop;
use common\models\warehousing\BlContainer;
use common\models\warehousing\BlContainerGoods;
use common\services\goods\GoodsService;
use common\services\sys\AccessService;
use Darabonba\GatewaySpi\Models\InterceptorContext\request;
use Yii;
use yii\helpers\ArrayHelper;

class BaseGoodsSearch extends Goods
{

    public $shop_id;
    public $ean;
    public $platform_sku_no;
    public $platform_goods_id;
    public $platform_goods_opc;
    public $platform_goods_exp_id;
    public $un_o_category_name;
    public $start_add_time;
    public $end_add_time;
    public $country_code;
    public $un_goods_short_name;
    public $un_admin_id;
    public $claim_shop_name;
    public $un_claim_shop_name;
    public $tag;
    public $follow_type;
    public $is_min_price;
    public $has_sales;
    public $has_inventory;
    public $has_transit;
    public $has_numbers;
    public $has_stock;

    public function rules()
    {
        return [
            [['goods_no','goods_name','sku_no','platform_sku_no','shop_id','ean','un_o_category_name','un_goods_short_name','country_code','un_admin_id','platform_goods_id','platform_goods_exp_id','platform_goods_opc','claim_shop_name','un_claim_shop_name'], 'string'],
            [['category_id','start_add_time','admin_id','end_add_time','tag','follow_type','is_min_price','has_sales','has_inventory','has_transit','has_numbers','has_stock'], 'integer'],
        ];
    }

    public function search($params,$platform_type)
    {
        $this->load($params);
        $where = [];
        $where['_join'][] = 'gs';

        $where['gs.platform_type'] = $platform_type;

        $this->tag = empty($this->tag)?0:$this->tag;
        if($this->tag != -1){
            $where['gs.status'] = $this->tag;
        }

        if (!empty($this->goods_no)) {
            $where['_join'][] = 'gs';
            $goods_no = explode(PHP_EOL,$this->goods_no);
            foreach ($goods_no as &$v){
                $v = trim($v);
            }
            $goods_no = array_filter($goods_no);
            $goods_no = count($goods_no) == 1?current($goods_no):$goods_no;
            $where['and'][] = ['gs.goods_no'=> $goods_no];
        }

        if (!empty($this->goods_name)) {
            $where['_join'][] = 'mg';
            $where['and'][] = ['like', 'mg.goods_name', $this->goods_name];
        }

        if(!empty($this->un_claim_shop_name)) {
            $where['_join'][] = 'gs';
            $this->un_claim_shop_name = array_filter($this->un_claim_shop_name);
            if (!empty($this->un_claim_shop_name)) {
                $platform = [];
                $shop_id = [];
                foreach ($this->un_claim_shop_name as $un_claim_shop_v) {
                    if (strpos($un_claim_shop_v, 'P_') !== false) {
                        $platform[] = str_replace('P_', '', $un_claim_shop_v);
                    } else {
                        $shop_id[] = $un_claim_shop_v;
                    }
                }
                if ($platform) {
                    $platform_shop_id = Shop::find()->where(['platform_type' => $platform])->select('id')->column();
                    $shop_id = array_merge($shop_id, $platform_shop_id);
                    $shop_id = array_filter($shop_id);
                }
                $where['and'][] = ['not in', 'gs.id', GoodsShop::find()->select('id')->where(['shop_id' => $shop_id])];
            }
        }

        if (!empty($this->sku_no)) {
            $sku_no = explode(PHP_EOL,$this->sku_no);
            foreach ($sku_no as &$v){
                $v = trim($v);
            }
            $sku_no = array_filter($sku_no);
            $sku_no = count($sku_no) == 1?current($sku_no):$sku_no;
            //$where['sku_no'] = $sku_no;
            $cgoods_no = GoodsChild::find()->where(['sku_no'=> $sku_no])->select('cgoods_no');
            $where['and'][] = ['gs.cgoods_no'=>$cgoods_no];
            $where['_join'][] = 'gs';
        }

        if (!empty($this->platform_sku_no)) {
            $platform_sku_no = explode(PHP_EOL,$this->platform_sku_no);
            foreach ($platform_sku_no as &$v){
                $v = trim($v);
            }
            $platform_sku_no = array_filter($platform_sku_no);
            $platform_sku_no = count($platform_sku_no) == 1?current($platform_sku_no):$platform_sku_no;
            $where['gs.platform_sku_no'] = $platform_sku_no;
            $where['_join'][] = 'gs';
        }

        if (!empty($this->platform_goods_exp_id)) {
            $platform_goods_exp_id = explode(PHP_EOL,$this->platform_goods_exp_id);
            foreach ($platform_goods_exp_id as &$v){
                $v = trim($v);
            }
            $platform_goods_exp_id = array_filter($platform_goods_exp_id);
            $platform_goods_exp_id = count($platform_goods_exp_id) == 1?current($platform_goods_exp_id):$platform_goods_exp_id;
            $where['gs.platform_goods_exp_id'] = $platform_goods_exp_id;
            $where['_join'][] = 'gs';
        }

        if (!empty($this->platform_goods_id)) {
            $platform_goods_id = explode(PHP_EOL,$this->platform_goods_id);
            foreach ($platform_goods_id as &$v){
                $v = trim($v);
            }
            $platform_goods_id = array_filter($platform_goods_id);
            $platform_goods_id = count($platform_goods_id) == 1?current($platform_goods_id):$platform_goods_id;
            $where['gs.platform_goods_id'] = $platform_goods_id;
            $where['_join'][] = 'gs';
        }

        if (!empty($this->platform_goods_opc)) {
            $platform_goods_opc = explode(PHP_EOL,$this->platform_goods_opc);
            foreach ($platform_goods_opc as &$v){
                $v = trim($v);
            }
            $platform_goods_opc = array_filter($platform_goods_opc);
            $platform_goods_opc = count($platform_goods_opc) == 1?current($platform_goods_opc):$platform_goods_opc;
            $where['gs.platform_goods_opc'] = $platform_goods_opc;
            $where['_join'][] = 'gs';
        }

        if (!empty($this->ean)) {
            $ean = explode(PHP_EOL,$this->ean);
            foreach ($ean as &$v){
                $v = trim($v);
            }
            $ean = array_filter($ean);
            $ean = count($ean) == 1?current($ean):$ean;
            $where['gs.ean'] = $ean;
            $where['_join'][] = 'gs';
        }

        if (!empty($this->category_id)) {
            $category = Category::collectionChildrenId($this->category_id);
            $category[] = $this->category_id;
            $where['g.category_id'] = $category;
            $where['_join'][] = 'g';
        }

        if(!empty($this->un_o_category_name)) {
            $where['mg.o_category_name'] = '';
            $where['_join'][] = 'mg';
        }

        if(!empty($this->un_goods_short_name)) {
            $where['mg.goods_short_name'] = '';
            $where['_join'][] = 'mg';
        }

        if(!empty($this->country_code)){
            $where['gs.country_code'] = $this->country_code;
            $where['_join'][] = 'mg';
        }

        //创建时间
        if (!empty($this->start_add_time)) {
            $where['and'][] = ['>=', 'gs.add_time', strtotime($this->start_add_time)];
        }
        if (!empty($this->end_add_time)) {
            //$where['and'][] = ['<', 'gs.add_time', strtotime($this->end_add_time) + 86400];
            $where['and'][] = ['<=', 'gs.add_time', strtotime($this->end_add_time)];
        }

        //店铺数据
        if(!AccessService::hasAllShop()) {
            $shop_id = Yii::$app->user->identity->shop_id;
            $shop_id = explode(',', $shop_id);
            $where['and'][] = ['in', 'shop_id', $shop_id];
        }

        if(!empty($this->shop_id)) {
            $where['and'][] = ['=', 'shop_id', $this->shop_id];
            unset($where['gs.platform_type']);
        }



        return $where;
    }

    public function platform_search($params,$tag)
    {
        $this->load($params);
        $where = [];
        $where['audit_status'] = $tag;
        $where['_join'][] = 'mg';

        if (!empty($this->goods_no)) {
            $goods_no = explode(PHP_EOL,$this->goods_no);
            foreach ($goods_no as &$v){
                $v = trim($v);
            }
            $goods_no = array_filter($goods_no);
            $goods_no = count($goods_no) == 1?current($goods_no):$goods_no;
            $where['and'][] = ['mg.goods_no'=> $goods_no];
        }

        if (!empty($this->goods_name)) {
            $where['_join'][] = 'g';
            $where['and'][] = ['like', 'g.goods_name', $this->goods_name];
        }

        if (!empty($this->sku_no)) {
            $sku_no = explode(PHP_EOL,$this->sku_no);
            foreach ($sku_no as &$v){
                $v = trim($v);
            }
            $sku_no = array_filter($sku_no);
            $sku_no = count($sku_no) == 1?current($sku_no):$sku_no;
            //$where['sku_no'] = $sku_no;
            $goods_no = GoodsChild::find()->where(['sku_no'=> $sku_no])->select('goods_no');
            $where['and'][] = ['mg.goods_no'=>$goods_no];
        }

        if (!empty($this->category_id)) {
            $category = Category::collectionChildrenId($this->category_id);
            $category[] = $this->category_id;
            $where['g.category_id'] = $category;
            $where['_join'][] = 'g';
        }

        if(!empty($this->un_o_category_name)) {
            $where['mg.o_category_name'] = '';
        }

        if(!empty($this->un_goods_short_name)) {
            $where['mg.goods_short_name'] = '';
        }

        if (!empty($this->admin_id)) {
            $where['and'][] = ['=','mg.admin_id',$this->admin_id];
        }

        if(!empty($this->un_admin_id)) {
            $this->un_admin_id = array_filter($this->un_admin_id);
            if (!empty($this->un_admin_id)) {
                $where['and'][] = ['not in','mg.admin_id',$this->un_admin_id];
            }
        }

        //已认领店铺
        if(!empty($this->claim_shop_name)) {
            $shop_id = $this->claim_shop_name;
            if (strpos($shop_id, 'P_') !== false) {
                $platform = str_replace('P_', '', $shop_id);
                $shop_id = Shop::find()->where(['platform_type' => $platform])->select('id')->column();
            }
            $where['and'][] = ['in', 'mg.goods_no', GoodsShop::find()->select('goods_no')->where(['shop_id' => $shop_id])];
        }

        //未认领店铺
        if(!empty($this->un_claim_shop_name)) {
            $this->un_claim_shop_name = array_filter($this->un_claim_shop_name);
            if (!empty($this->un_claim_shop_name)) {
                $platform = [];
                $shop_id = [];
                foreach ($this->un_claim_shop_name as $un_claim_shop_v) {
                    if (strpos($un_claim_shop_v, 'P_') !== false) {
                        $platform[] = str_replace('P_', '', $un_claim_shop_v);
                    } else {
                        $shop_id[] = $un_claim_shop_v;
                    }
                }
                if ($platform) {
                    $platform_shop_id = Shop::find()->where(['platform_type' => $platform])->select('id')->column();
                    $shop_id = array_merge($shop_id, $platform_shop_id);
                    $shop_id = array_filter($shop_id);
                }
                $where['and'][] = ['not in', 'mg.goods_no', GoodsShop::find()->select('goods_no')->where(['shop_id' => $shop_id])];
            }
        }

        //创建时间
        if (!empty($this->start_add_time)) {
            $where['and'][] = ['>=', 'mg.add_time', strtotime($this->start_add_time)];
        }
        if (!empty($this->end_add_time)) {
            $where['and'][] = ['<=', 'mg.add_time', strtotime($this->end_add_time)];
        }

        if (!AccessService::hasAllGoods()) {
            $where['mg.admin_id'] = Yii::$app->user->id;
        }

        return $where;
    }

    public function shop_follow_sale_search($params,$platform_type)
    {
        $this->load($params);
        $where = [];
        $where['_join'][] = 'gsf';
        $where['_join'][] = 'gs';

        $where['gs.platform_type'] = $platform_type;
        $where['gsf.platform_type'] = $platform_type;
        $where['gsf.type'] = [GoodsShopFollowSale::TYPE_FOLLOW,GoodsShopFollowSale::TYPE_NON_CHINA,GoodsShopFollowSale::TYPE_LOW_PRICE_FOLLOW,GoodsShopFollowSale::TYPE_UNFOLLOW_FOLLOW];

        if (!empty($this->goods_no)) {
            $where['_join'][] = 'gs';
            $goods_no = explode(PHP_EOL,$this->goods_no);
            foreach ($goods_no as &$v){
                $v = trim($v);
            }
            $goods_no = array_filter($goods_no);
            $goods_no = count($goods_no) == 1?current($goods_no):$goods_no;
            $where['and'][] = ['gs.goods_no'=> $goods_no];
        }

        if (!empty($this->goods_name)) {
            $where['_join'][] = 'mg';
            $where['and'][] = ['like', 'mg.goods_name', $this->goods_name];
        }

        if (!empty($this->sku_no)) {
            $sku_no = explode(PHP_EOL, $this->sku_no);
            foreach ($sku_no as &$v) {
                $v = trim($v);
            }
            $sku_no = array_filter($sku_no);
            $sku_no = count($sku_no) == 1 ? current($sku_no) : $sku_no;
            //$where['sku_no'] = $sku_no;
            $cgoods_no = GoodsChild::find()->where(['sku_no' => $sku_no])->select('cgoods_no');
            $where['and'][] = ['gs.cgoods_no' => $cgoods_no];
            $where['_join'][] = 'gs';
        }
        //创建时间
        if (!empty($this->start_add_time)) {
            $where['and'][] = ['>=', 'gsf.add_time', strtotime($this->start_add_time)];
        }

        if (!empty($this->end_add_time)) {
            //$where['and'][] = ['<', 'gs.add_time', strtotime($this->end_add_time) + 86400];
            $where['and'][] = ['<=', 'gsf.add_time', strtotime($this->end_add_time)];
        }

        //店铺数据
        if(!AccessService::hasAllShop()) {
            $shop_id = Yii::$app->user->identity->shop_id;
            $shop_id = explode(',', $shop_id);
            $where['and'][] = ['in', 'gsf.shop_id', $shop_id];
        }

        if(!empty($this->shop_id)) {
            $where['and'][] = ['=', 'gsf.shop_id', $this->shop_id];
            unset($where['gsf.platform_type']);
        }

        if (!empty($this->category_id)) {
            $category = Category::collectionChildrenId($this->category_id);
            $category[] = $this->category_id;
            $where['g.category_id'] = $category;
            $where['_join'][] = 'g';
        }

        if (!empty($this->ean)) {
            $ean = explode(PHP_EOL,$this->ean);
            foreach ($ean as &$v){
                $v = trim($v);
            }
            $ean = array_filter($ean);
            $ean = count($ean) == 1?current($ean):$ean;
            $where['gs.ean'] = $ean;
            $where['_join'][] = 'gs';
        }

        if (!empty($this->platform_sku_no)) {
            $platform_sku_no = explode(PHP_EOL,$this->platform_sku_no);
            foreach ($platform_sku_no as &$v){
                $v = trim($v);
            }
            $platform_sku_no = array_filter($platform_sku_no);
            $platform_sku_no = count($platform_sku_no) == 1?current($platform_sku_no):$platform_sku_no;
            $where['gs.platform_sku_no'] = $platform_sku_no;
            $where['_join'][] = 'gs';
        }

        if (!empty($this->follow_type)) {
            $where['gsf.type'] = $this->follow_type;
        }

        $is_min_price = $this->is_min_price;
        if ($is_min_price != '') {
            $where['gsf.is_min_price'] = $is_min_price;
        }

        if (!empty($this->has_sales)) {
            $where['and'][] = ['>', 'st.total_sales', 0];
        }

        return $where;
    }

    public function shop_follow_sale_log_search($params,$platform_type,$goods_shop_id)
    {
        $this->load($params);
        $where = [];
        $where['_join'][] = 'gsfsl';

        $where['gsf.platform_type'] = $platform_type;
        $where['gsfsl.platform_type'] = $platform_type;
        $where['gsfsl.goods_shop_id'] = $goods_shop_id;

        return $where;
    }

    /**
     * 海外仓搜索
     * @param $params
     * @param $platform_type
     * @return array
     */
    public function overseas_search($params,$platform_type)
    {
        $this->load($params);
        $where = [];
        $where['_join'][] = 'gsow';
        $where['_join'][] = 'gs';

        $where['gs.platform_type'] = $platform_type;
        $where['gsow.platform_type'] = $platform_type;

        if (!empty($this->goods_no)) {
            $where['_join'][] = 'gs';
            $goods_no = explode(PHP_EOL,$this->goods_no);
            foreach ($goods_no as &$v){
                $v = trim($v);
            }
            $goods_no = array_filter($goods_no);
            $goods_no = count($goods_no) == 1?current($goods_no):$goods_no;
            $where['and'][] = ['gs.goods_no'=> $goods_no];
        }

        if (!empty($this->platform_goods_id)) {
            $where['_join'][] = 'gs';
            $platform_goods_id = explode(PHP_EOL,$this->platform_goods_id);
            foreach ($platform_goods_id as &$v){
                $v = trim($v);
            }
            $platform_goods_id = array_filter($platform_goods_id);
            $platform_goods_id = count($platform_goods_id) == 1?current($platform_goods_id):$platform_goods_id;
            $where['and'][] = ['gs.platform_goods_id'=> $platform_goods_id];
        }

        if (!empty($this->platform_goods_opc)) {
            $where['_join'][] = 'gs';
            $platform_goods_opc = explode(PHP_EOL,$this->platform_goods_opc);
            foreach ($platform_goods_opc as &$v){
                $v = trim($v);
            }
            $platform_goods_opc = array_filter($platform_goods_opc);
            $platform_goods_opc = count($platform_goods_opc) == 1?current($platform_goods_opc):$platform_goods_opc;
            $where['and'][] = ['gs.platform_goods_opc'=> $platform_goods_opc];
        }

        if (!empty($this->goods_name)) {
            $where['_join'][] = 'mg';
            $where['and'][] = ['like', 'mg.goods_name', $this->goods_name];
        }

        if(!empty($this->country_code)){
            $where['gs.country_code'] = $this->country_code;
            $where['_join'][] = 'mg';
        }

        if (!empty($this->sku_no)) {
            $sku_no = explode(PHP_EOL, $this->sku_no);
            foreach ($sku_no as &$v) {
                $v = trim($v);
            }
            $sku_no = array_filter($sku_no);
            $sku_no = count($sku_no) == 1 ? current($sku_no) : $sku_no;
            //$where['sku_no'] = $sku_no;
            $cgoods_no = GoodsChild::find()->where(['sku_no' => $sku_no])->select('cgoods_no');
            $where['and'][] = ['gs.cgoods_no' => $cgoods_no];
            $where['_join'][] = 'gs';
        }

        //店铺数据
        if(!AccessService::hasAllShop()) {
            $shop_id = Yii::$app->user->identity->shop_id;
            $shop_id = explode(',', $shop_id);
            $where['and'][] = ['in', 'gsow.shop_id', $shop_id];
        }

        if(!empty($this->shop_id)) {
            $where['and'][] = ['=', 'gsow.shop_id', $this->shop_id];
            unset($where['gsow.platform_type']);
        }

        if (!empty($this->category_id)) {
            $category = Category::collectionChildrenId($this->category_id);
            $category[] = $this->category_id;
            $where['g.category_id'] = $category;
            $where['_join'][] = 'g';
        }

        if (!empty($this->ean)) {
            $ean = explode(PHP_EOL,$this->ean);
            foreach ($ean as &$v){
                $v = trim($v);
            }
            $ean = array_filter($ean);
            $ean = count($ean) == 1?current($ean):$ean;
            $where['gs.ean'] = $ean;
            $where['_join'][] = 'gs';
        }

        if (!empty($this->platform_sku_no)) {
            $platform_sku_no = explode(PHP_EOL,$this->platform_sku_no);
            foreach ($platform_sku_no as &$v){
                $v = trim($v);
            }
            $platform_sku_no = array_filter($platform_sku_no);
            $platform_sku_no = count($platform_sku_no) == 1?current($platform_sku_no):$platform_sku_no;
            $where['gs.platform_sku_no'] = $platform_sku_no;
            $where['_join'][] = 'gs';
        }

        if (!empty($this->has_numbers)) {
            $has_numbers = $this->has_numbers;
            $has_transit = GoodsShopOverseasWarehouse::find()->alias('gsow')
                ->leftJoin(BlContainerGoods::tableName(). ' bcg','bcg.cgoods_no = gsow.cgoods_no and bcg.warehouse_id = gsow.warehouse_id and bcg.status = 10')
                ->select('gsow.id')->where(['gsow.platform_type' => $platform_type])->groupBy('gsow.id');
            if (in_array(1,$has_numbers)) {
                $where['and'][] = ['>', 'st.total_sales', 0];
            }
            if (in_array(2,$has_numbers)) {
                $gsow_id = GoodsShopOverseasWarehouse::find()
                    ->alias('gsow')->select('gsow.id')
                    ->leftJoin(GoodsShop::tableName() . ' gs', 'gs.id = gsow.goods_shop_id')
                    ->leftJoin(GoodsShopSalesTotal::tableName() . ' st', 'gs.id = st.goods_shop_id')
                    ->where(['gsow.platform_type' => $platform_type])
                    ->having('(case when st.total_sales is not null then st.total_sales else 0 end) = 0');
                $where['and'][] = ['in', 'gsow.id', $gsow_id];
            }
            if (in_array(3,$has_numbers)) {
                $where['and'][] = ['>','gsow.goods_stock',0];
            }
            if (in_array(4,$has_numbers)) {
                $where['and'][] = ['<=','gsow.goods_stock',0];
            }
            if (in_array(5,$has_numbers)) {
                $overseas_id = $has_transit->having('sum(bcg.num) > 0');
                $where['and'][] = ['in','gsow.id',$overseas_id];
            }
            if (in_array(6,$has_numbers)) {
                $overseas_id = $has_transit->having('sum(bcg.num) is null');
                $where['and'][] = ['in','gsow.id',$overseas_id];
            }
        }

        if (!empty($this->has_stock)) {
            $has_stock = $this->has_stock;
            if ($has_stock == 1) {
                $star_time = GoodsService::getBeforeTime('-60');
                $end_time = GoodsService::getBeforeTime('-30');
            }elseif ($has_stock == 2) {
                $star_time = GoodsService::getBeforeTime('-90');
                $end_time = GoodsService::getBeforeTime('-60');
            }elseif ($has_stock == 3) {
                $star_time = GoodsService::getBeforeTime('-180');
                $end_time = GoodsService::getBeforeTime('-90');
            }elseif ($has_stock == 4) {
                $star_time = GoodsService::getBeforeTime('-360');
                $end_time = GoodsService::getBeforeTime('-180');
            }

            if ($has_stock != 5) {
                $wheres = 'gsd.inbound_time >= '.$star_time.' and gsd.inbound_time < '.$end_time;
            } else {
                $wheres = 'gsd.inbound_time < '.GoodsService::getBeforeTime('-360');
            }
            $gsow_id = GoodsShopOverseasWarehouse::find()->alias('gsow')
                ->select('gsow.id')
                ->leftJoin(GoodsStockDetails::tableName().' gsd','gsd.cgoods_no = gsow.cgoods_no and gsd.warehouse = gsow.warehouse_id')
                ->where($wheres)->andWhere(['gsd.status' => 2])->andWhere(['=','gsd.outgoing_time',0]);
            $where['and'][] = ['in', 'gsow.id', $gsow_id];
            $where['has_stock'] = $wheres;
        }

        return $where;
    }

}