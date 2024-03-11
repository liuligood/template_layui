<?php
namespace backend\models\search;

use common\components\CommonUtil;
use common\models\Category;
use common\models\FindGoods;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsDistributionWarehouse;
use common\models\goods\GoodsStock;
use common\models\GoodsShop;
use common\models\Shop;
use common\services\goods\GoodsService;
use common\services\sys\AccessService;
use Yii;
use yii\data\ActiveDataProvider;

class GoodsSearch extends Goods
{
    public $start_add_time;
    public $end_add_time;
    public $claim_shop_name;
    public $un_claim_shop_name;
    public $un_owner_id;
    public $goods_tort_type_sel;
    public $distribution_warehouse_id;
    public $cgoods_no;
    public $has_warehouse_num;


    public function rules()
    {
        return [
            [['id','source_method','category_id','start_add_time','end_add_time','stock','admin_id','owner_id','status','source_platform_type','source_platform_category_id','goods_stamp_tag','goods_tort_type','goods_type','distribution_warehouse_id','has_warehouse_num','source_platform_id'], 'integer'],
            [['goods_no','cgoods_no','goods_name','sku_no','claim_shop_name','un_claim_shop_name','un_owner_id','goods_tort_type_sel','exclude_claim_shop_name','source_platform_title','colour'], 'string'],
        ];
    }

    public function search($params,$tag,$source_method)
    {
        $goods_stamp_tag = $this->goods_stamp_tag;
        $goods_tort_type = $this->goods_tort_type;
        $this->load($params);
        if(empty($this->goods_stamp_tag)){
            $this->goods_stamp_tag = $goods_stamp_tag;
        }
        if(empty($this->goods_tort_type)){
            $this->goods_tort_type = $goods_tort_type;
        }
        $where = [];

        if($source_method != 3) {
            //$where['source_method'] = $source_method;
        }

        switch ($tag) {
            case 1://未补充
                $where['status'] = Goods::GOODS_STATUS_WAIT_ADDED;
                break;
            case 2://已确认
                $where['status'] = [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_INVALID];
                //$where['and'][] = ['!=','goods_stamp_tag',Goods::GOODS_STAMP_TAG_FINE];
                $where['source_method_sub'] = 0;
                break;
            case 3://未分配
                $where['status'] = [Goods::GOODS_STATUS_UNALLOCATED, Goods::GOODS_STATUS_WAIT_ADDED, Goods::GOODS_STATUS_UNCONFIRMED];
                break;
            case 4://待匹配
                $where['status'] = Goods::GOODS_STATUS_WAIT_MATCH;
                //$where['and'][] = ['!=','goods_stamp_tag',Goods::GOODS_STAMP_TAG_FINE];
                //$where['goods_stamp_tag'] = 0;
                $where['source_method_sub'] = Goods::GOODS_SOURCE_METHOD_SUB_GRAB;
                break;
            case 5://精
                $where['status'] = [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_INVALID];
                //$where['goods_stamp_tag'] = Goods::GOODS_STAMP_TAG_FINE;
                $where['source_method_sub'] = GoodsService::getSourceMethodSubCombinations(Goods::GOODS_SOURCE_METHOD_SUB_FINE);
                break;
            case 6://精 待匹配
                $where['status'] = [Goods::GOODS_STATUS_WAIT_MATCH];
                //$where['goods_stamp_tag'] = Goods::GOODS_STAMP_TAG_FINE;
                $where['source_method_sub'] = GoodsService::getSourceMethodSubCombinations([Goods::GOODS_SOURCE_METHOD_SUB_GRAB, Goods::GOODS_SOURCE_METHOD_SUB_FINE]);
                break;
            case 7://分销
                $where['status'] = [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_INVALID];
                //$where['goods_stamp_tag'] = Goods::GOODS_STAMP_TAG_FINE;
                $where['source_method_sub'] = GoodsService::getSourceMethodSubCombinations(Goods::GOODS_SOURCE_METHOD_SUB_DISTRIBUTION);
                break;
        }

        if(in_array($tag,[2,4,5,6]) && $source_method == GoodsService::SOURCE_METHOD_OWN) {
            if($this->goods_tort_type != -1) {
                $where['goods_tort_type'] = $this->goods_tort_type;
            }
        }

        if(!empty($this->goods_tort_type_sel)) {
            $this->goods_tort_type_sel = array_filter($this->goods_tort_type_sel);
            if(!empty($this->goods_tort_type_sel)) {
                $where['and'][] = ['in', 'goods_tort_type', $this->goods_tort_type_sel];
            }
        }

        if (!empty($this->goods_type)) {
            $where['goods_type'] = $this->goods_type;
        }

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
            $where['goods_no'] = $goods_no;
        }

        if (!empty($this->id)) {
            $where['id'] = $this->id;
        }

        if(!empty($this->colour)) {
            if($this->colour == '无'){
                $where['colour'] = '';
            }else{
                $where['colour'] = $this->colour;
            }
        }

        if ($this->goods_stamp_tag == -2) {
            $where['status'] = Goods::GOODS_STATUS_INVALID;
            unset($where['goods_stamp_tag']);
        }else if ($this->goods_stamp_tag == -1) {
            unset($where['goods_stamp_tag']);
        }else if (!empty($this->goods_stamp_tag)) {
            $where['goods_stamp_tag'] = $this->goods_stamp_tag;
        }

        if (!empty($this->sku_no)) {
            $sku_no = explode(PHP_EOL,$this->sku_no);
            foreach ($sku_no as &$v){
                $v = trim($v);
            }
            $sku_no = array_filter($sku_no);
            $sku_no = count($sku_no) == 1?current($sku_no):$sku_no;
            if($tag == 10){
                $goods_nos = GoodsShop::find()->where(['platform_sku_no'=>$sku_no])->select('goods_no')->column();
                $where['and'][] = ['or',[
                    'goods_no' => $goods_nos],[
                    'sku_no' => $sku_no
                ]];
            } else {
                $where['sku_no'] = $sku_no;
            }
        }

        if (!empty($this->admin_id)) {
            $where['and'][] = ['=','admin_id',$this->admin_id];
        }

        if (!empty($this->owner_id)) {
            $where['and'][] = ['=','owner_id',$this->owner_id];
        }

        if(!empty($this->un_owner_id)) {
            $this->un_owner_id = array_filter($this->un_owner_id);
            if (!empty($this->un_owner_id)) {
                $where['and'][] = ['not in','owner_id',$this->un_owner_id];
            }
        }

        if (!empty($this->status)) {
            $where['and'][] = ['=','status',$this->status];
        }

         if(!empty($this->source_platform_category_id)){
             $where['source_platform_category_id'] = $this->source_platform_category_id;
         }

        if(!empty($this->source_platform_type)){
            $where['source_platform_type'] = $this->source_platform_type;
        }

        if(!empty($this->source_platform_title)){
            $where['source_platform_title'] = $this->source_platform_title;
        }

        if(!empty($this->distribution_warehouse_id)) {
            $where['and'][] = ['in', 'goods_no', GoodsDistributionWarehouse::find()->select('goods_no')->where(['warehouse_id' => $this->distribution_warehouse_id])];
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

        if (isset($this->stock) && $this->stock != '') {
            $where['stock'] = $this->stock;
        }

        //已认领店铺
        if(!empty($this->claim_shop_name)){
            //$shop_id = Shop::find()->where(['name'=>$this->claim_shop_name])->select('id')->scalar();
            $shop_id = $this->claim_shop_name;
            if (strpos($shop_id,'find_') !== false) {
                $platform = str_replace('find_','',$shop_id);
                $where['and'][] = ['in','goods_no',FindGoods::find()->select('goods_no')->where(['platform_type' => $platform])];
            } else {
                if(strpos($shop_id,'P_') !== false){
                    $platform = str_replace('P_','',$shop_id);
                    $shop_id = Shop::find()->where(['platform_type' => $platform])->select('id')->column();
                }
                $where['and'][] = ['in','goods_no',GoodsShop::find()->select('goods_no')->where(['shop_id'=>$shop_id])->distinct()];
            }
        }

        //未认领店铺
        /*if(!empty($this->un_claim_shop_name)){
            $shop_id = Shop::find()->where(['name'=>$this->un_claim_shop_name])->select('id')->scalar();
            $where['and'][] = ['not in','goods_no',GoodsShop::find()->select('goods_no')->where(['shop_id'=>$shop_id])];
        }*/
        if(!empty($this->un_claim_shop_name)){
            $this->un_claim_shop_name = array_filter($this->un_claim_shop_name);
            if(!empty($this->un_claim_shop_name)) {
                $platform = [];
                $shop_id = [];
                $find_goods = [];
                foreach ($this->un_claim_shop_name as $un_claim_shop_v){
                    if (strpos($un_claim_shop_v,'find_') !== false) {
                        $platform_type = str_replace('find_','',$un_claim_shop_v);
                        $find_goods[] = $platform_type;
                    }
                    if(strpos($un_claim_shop_v,'P_') !== false){
                        $platform[] = str_replace('P_','',$un_claim_shop_v);
                    }else {
                        $shop_id[] = $un_claim_shop_v;
                    }
                }
                if (!empty($find_goods)) {
                    $where['and'][] = ['not in', 'goods_no', FindGoods::find()->select('goods_no')->where(['platform_type' => $find_goods])];
                }
                if($platform) {
                    $platform_shop_id = Shop::find()->where(['platform_type' => $platform])->select('id')->column();
                    $shop_id = array_merge($shop_id,$platform_shop_id);
                    $shop_id = array_filter($shop_id);
                }
                $where['and'][] = ['not in', 'goods_no', GoodsShop::find()->select('goods_no')->where(['shop_id' => $shop_id])->distinct()];
            }
        }

        //时间
        if (!empty($this->start_add_time)) {
            $where['and'][] = ['>=', 'add_time', strtotime($this->start_add_time)];
        }
        if (!empty($this->end_add_time)) {
            //$where['and'][] = ['<', 'add_time', strtotime($this->end_add_time) + 86400];
            $where['and'][] = ['<=', 'add_time', strtotime($this->end_add_time)];
        }

        //店铺数据
        if($source_method == GoodsService::SOURCE_METHOD_OWN && in_array($tag,[1,2,4,6,5,7])) {
            if (!AccessService::hasAllGoods()) {
                if (AccessService::hasOwnerGoods()) {
                    $where['owner_id'] = Yii::$app->user->id;
                } else {
                    $where['admin_id'] = Yii::$app->user->id;
                }
            }
        }

        if((!empty($this->start_add_time) || !empty($this->end_add_time)) && !empty($this->source_platform_type)){
            $where['use_index'] = 'status_source_method_sub_add_time';
        }

        return $where;
    }

    public function selectSearch($params,$tag)
    {
        $this->load($params);
        $where = [];
        switch ($tag) {
            case 1://库存
                $where['g.status'] = [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_INVALID,Goods::GOODS_STATUS_WAIT_MATCH];
                break;
        }

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
            $where['gc.goods_no'] = $goods_no;
        }

        if (!empty($this->cgoods_no)) {
            $cgoods_no = explode(PHP_EOL,$this->cgoods_no);
            foreach ($cgoods_no as &$v){
                $v = trim($v);
            }
            $cgoods_no = array_filter($cgoods_no);
            $cgoods_no = count($cgoods_no) == 1?current($cgoods_no):$cgoods_no;
            $where['gc.cgoods_no'] = $cgoods_no;
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

        if (!empty($this->category_id)) {
            if ($this->category_id == -1) {
                $category = 0;
            }else{
                $category = Category::collectionChildrenId($this->category_id);
                $category[] = $this->category_id;
            }
            $where['category_id'] = $category;
        }

        //店铺数据
        if(in_array($tag,[1])) {
            if (!AccessService::hasAllGoods()) {
                if (AccessService::hasOwnerGoods()) {
                    $where['owner_id'] = Yii::$app->user->id;
                } else {
                    $where['admin_id'] = Yii::$app->user->id;
                }
            }
        }

        return $where;
    }


    public function distribution_search($params, $source_method, $warehouse_id)
    {
        $where = [];

        $this->load($params);

        $goods_stamp_tag = $this->goods_stamp_tag;
        $goods_tort_type = $this->goods_tort_type;

        if(empty($this->goods_stamp_tag)){
            $this->goods_stamp_tag = $goods_stamp_tag;
        }
        if(empty($this->goods_tort_type)){
            $this->goods_tort_type = $goods_tort_type;
        }

        $where['g.status'] = [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_INVALID];

        $where['g.source_method_sub'] = GoodsService::getSourceMethodSubCombinations(Goods::GOODS_SOURCE_METHOD_SUB_DISTRIBUTION);

        $where['gs.warehouse'] = $warehouse_id;

        if (!empty($this->has_warehouse_num)) {
            $goods_no = GoodsStock::find()->alias('gs')
                ->select('g.goods_no')
                ->leftJoin(GoodsChild::tableName().' gc','gc.cgoods_no = gs.cgoods_no')
                ->leftJoin(Goods::tableName().' g','g.goods_no = gc.goods_no')
                ->where($where)->groupBy('g.goods_no')->having('sum(gs.num) > 0');
            $where['and'][] = ['in', 'g.goods_no', $goods_no];
        }

        if (!empty($this->goods_type)) {
            $where['goods_type'] = $this->goods_type;
        }

        if (!empty($this->source_platform_id)) {
            $source_platform_id = explode(PHP_EOL,$this->source_platform_id);
            foreach ($source_platform_id as &$v){
                $v = trim($v);
            }
            $source_platform_id = array_filter($source_platform_id);
            $source_platform_id = count($source_platform_id) == 1?current($source_platform_id):$source_platform_id;
            $where['g.source_platform_id'] = $source_platform_id;
        }

        if (!empty($this->goods_name)) {
            $goods_name = CommonUtil::searchWork($this->goods_name);
            $where['and'][] = "MATCH (g.goods_name) AGAINST ('".$goods_name."' IN BOOLEAN MODE)";
        }

        if (!empty($this->goods_no)) {
            $goods_no = explode(PHP_EOL,$this->goods_no);
            foreach ($goods_no as &$v){
                $v = trim($v);
            }
            $goods_no = array_filter($goods_no);
            $goods_no = count($goods_no) == 1?current($goods_no):$goods_no;
            $where['g.goods_no'] = $goods_no;
        }

        if (!empty($this->sku_no)) {
            $sku_no = explode(PHP_EOL,$this->sku_no);
            foreach ($sku_no as &$v){
                $v = trim($v);
            }
            $sku_no = array_filter($sku_no);
            $sku_no = count($sku_no) == 1?current($sku_no):$sku_no;
            $where['g.sku_no'] = $sku_no;
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
            if(strpos($shop_id,'P_') !== false){
                $platform = str_replace('P_','',$shop_id);
                $shop_id = Shop::find()->where(['platform_type' => $platform])->select('id')->column();
            }
            $where['and'][] = ['in','g.goods_no',GoodsShop::find()->select('goods_no')->where(['shop_id'=>$shop_id])->distinct()];
        }

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
                $where['and'][] = ['not in', 'g.goods_no', GoodsShop::find()->select('goods_no')->where(['shop_id' => $shop_id])->distinct()];
            }
        }

        //时间
        if (!empty($this->start_add_time)) {
            $where['and'][] = ['>=', 'g.add_time', strtotime($this->start_add_time)];
        }
        if (!empty($this->end_add_time)) {
            $where['and'][] = ['<=', 'g.add_time', strtotime($this->end_add_time)];
        }

        //店铺数据
        if($source_method == GoodsService::SOURCE_METHOD_OWN) {
            if (!AccessService::hasAllGoods()) {
                if (AccessService::hasOwnerGoods()) {
                    $where['g.owner_id'] = Yii::$app->user->id;
                } else {
                    $where['g.admin_id'] = Yii::$app->user->id;
                }
            }
        }

        return $where;
    }
}