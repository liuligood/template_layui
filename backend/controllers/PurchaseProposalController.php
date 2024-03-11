<?php

namespace backend\controllers;

use backend\models\AdminUser;
use backend\models\search\PurchaseProposalSearch;
use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsStock;
use common\models\GoodsSource;
use common\models\Order;
use common\models\OrderGoods;
use common\models\purchase\PurchaseProposal;
use common\models\Shop;
use common\models\SupplierRelationship;
use common\models\User;
use common\services\goods\FGoodsService;
use common\services\goods\GoodsService;
use common\services\purchase\PurchaseProposalService;
use common\services\sys\AccessService;
use common\services\sys\CountryService;
use Yii;
use common\base\BaseController;
use yii\helpers\ArrayHelper;
use yii\web\Response;

class PurchaseProposalController extends BaseController
{
    /**
     * 获取model
     * @return \common\models\BaseAR
     */
    public function model(){
        return new PurchaseProposal();
    }

    /**
     * @routeName 采购建议管理
     * @routeDescription 采购建议管理
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $tag = $req->get('tag', 2);
        $category_cuts = PurchaseProposal::find()->where(['warehouse' => $tag])->select('category_id,count(*) cut')
            ->groupBy('category_id')->asArray()->all();
        foreach ($category_cuts as $val) {
            $key_arrays[] = $val['cut'];
        }
        array_multisort($key_arrays, SORT_DESC, SORT_NUMERIC, $category_cuts);

        $category_id = ArrayHelper::getColumn($category_cuts, 'category_id');
        $category_lists = Category::find()->select('name,id')->where(['source_method' => 1, 'id' => $category_id])->indexBy('id')->asArray()->all();
        $category_arr = [];
        foreach ($category_cuts as $v) {
            if(empty($v['category_id']) || empty($category_lists[$v['category_id']])){
                continue;
            }
            $category_arr[$v['category_id']] = $category_lists[$v['category_id']]['name'] . '（' . $v['cut'] . '）';
        }

        $admin_lists = AdminUser::find()->where(['id' => AccessService::getPurchaseUserIds()])->andWhere(['=','status',AdminUser::STATUS_ACTIVE])->select(['id','nickname'])->asArray()->all();
        $admin_lists = ArrayHelper::map($admin_lists,'id','nickname');
        $admin_lists = ['-1'=>'未分配']+$admin_lists;

        return $this->render('index', ['tag' => $tag,
            'category_arr' => $category_arr,
            'all_goods_access' => AccessService::hasAllPurchaseGoods(),
            'admin_arr' => $admin_lists,
        ]);
    }

    public function query($type = 'select')
    {
        $query = PurchaseProposal::find()
            ->alias('pp')->select('pp.*,g.category_id,g.status as goods_status,g.stock as stock_status,g.goods_img,g.goods_name,g.goods_name_cn,g.price,g.weight,g.size,g.id as goods_id,g.electric,g.real_weight,g.specification');
        $query->leftJoin(Goods::tableName() . ' g', 'g.goods_no= pp.goods_no');
        return $query;
    }

    /**
     * @routeName 采购建议列表
     * @routeDescription 采购建议列表
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $req = Yii::$app->request;
        $tag = $req->get('tag', 1);

        $searchModel = new PurchaseProposalSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams, $tag);
        $data = $this->lists($where, 'id asc');

        $goods_nos = ArrayHelper::getColumn($data['list'], 'goods_no');
        $goods_source = GoodsSource::find()->where(['goods_no' => $goods_nos, 'platform_type' => Base::PLATFORM_1688, 'is_main' => 2])->indexBy('goods_no')->asArray()->all();

        $goods_source_reference = GoodsSource::find()->where(['goods_no' => $goods_nos, 'platform_type' => Base::PLATFORM_1688, 'is_main' => [0, 1]])->indexBy('goods_no')->asArray()->all();

        $goods_supplier_reference = SupplierRelationship::find()->where(['goods_no' => $goods_nos,'is_prior'=>1])->indexBy('goods_no')->asArray()->all();
        //获取sku销量
        $sku_nos = ArrayHelper::getColumn($data['list'], 'sku_no');
        $sku_num_lists = OrderGoods::find()->where(['platform_asin' => $sku_nos])->select('platform_asin,count(goods_num) cut')->groupBy('platform_asin')->indexBy('platform_asin')->asArray()->all();

        $all_order_lists = (new PurchaseProposalService())->getOrderQuery('og.id as id,o.id as oid,o.currency,o.exchange_rate,o.country,source,platform_asin as sku_no,og.cgoods_no,o.order_id,goods_num as num,goods_income_price as price,platform_fee,shop_id,date', $sku_nos, $tag);
        $all_order_lists = ArrayHelper::index($all_order_lists, null, 'sku_no');

        $cgoods_nos = ArrayHelper::getColumn($data['list'], 'cgoods_no');
        $goods_childs = GoodsChild::find()->where(['cgoods_no' => $cgoods_nos])->indexBy('cgoods_no')->asArray()->all();

        $shop_lists = Shop::find()->select('id,name,currency')->indexBy('id')->asArray()->all();
        $lists = array_map(function ($info) use ($goods_source, $goods_source_reference, $shop_lists, $sku_num_lists, $all_order_lists, $goods_childs,$goods_supplier_reference) {
            $goods_child = !empty($goods_childs[$info['cgoods_no']]) ? $goods_childs[$info['cgoods_no']] : [];
            if (empty($goods_child['goods_img'])) {
                $image = json_decode($info['goods_img'], true);
                $image = empty($image) || !is_array($image) ? '' : current($image)['img'];
            } else {
                $image = $goods_child['goods_img'];
            }
            $info['image'] = $image;
            $info['electric_desc'] = Base::$electric_map[(empty($info['electric']) ? 0 : $info['electric'])];
            $info['purchase_title'] = !empty($goods_source[$info['goods_no']]) ? $goods_source[$info['goods_no']]['platform_title'] : '';
            $info['purchase_url'] = !empty($goods_source[$info['goods_no']]) ? $goods_source[$info['goods_no']]['platform_url'] : '';
            $info['reference_purchase_url'] = !empty($goods_source_reference[$info['goods_no']]) ? $goods_source_reference[$info['goods_no']]['platform_url'] : '';
            $info['has_supplier'] = !empty($goods_supplier_reference[$info['goods_no']])?1:0;
            $info['real_weight'] = $info['real_weight'] == 0 ? $info['weight'] : $info['real_weight'];
            $info['all_order'] = empty($sku_num_lists[$info['sku_no']]) ? 0 : $sku_num_lists[$info['sku_no']]['cut'];//销量
            $info['price'] = !empty($goods_source_reference[$info['goods_no']]) ? $goods_source_reference[$info['goods_no']]['price'] : (!empty($goods_source[$info['goods_no']]) ? $goods_source[$info['goods_no']]['price'] : '');
            $info['ccolour'] = !empty($goods_child['colour']) ? $goods_child['colour'] : '';
            $info['csize'] = !empty($goods_child['size']) ? $goods_child['size'] : '';
            $user = User::getInfo($info['admin_id']);
            $info['admin_name'] = empty($user['nickname']) ? '' : $user['nickname'];

            $order_lists = empty($all_order_lists[$info['sku_no']]) ? [] : $all_order_lists[$info['sku_no']];
            foreach ($order_lists as &$v) {
                $platform_fee = FGoodsService::factory($v['source'])->setCountryCode($v['country'])->platformFee($v['price'],$v['shop_id']);
                $v['price'] = round($v['price'] - $platform_fee, 2);
                $v['rmb_price'] = CountryService::getConvertRMB($v['price'], $v['currency']);
                $v['add_time'] = date('Y-m-d H:i', $v['date']);
                $v['shop_name'] = $shop_lists[$v['shop_id']]['name'];
                $v['country'] = CountryService::getName($v['country'], false, true);
                $order = Order::find()->where(['order_id'=>$v['order_id']])->select('remarks')->one();
                $v['remarks'] = $order['remarks'];
                $v['has_ov_stock'] = false;
                if(CountryService::isEuropeanUnion($v['country'])) {
                    $goods_stocks = GoodsStock::find()->where(['warehouse' => 8, 'cgoods_no' => $v['cgoods_no']])->asArray()->one();
                    if(!empty($goods_stocks) && $goods_stocks['num'] > $v['num']) {
                        $v['has_ov_stock'] = true;
                    }
                }
            }
            $info['order_lists'] = $order_lists;
            return $info;
        }, $data['list']);

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $data['pages']->totalCount
        );
    }

    /**
     *
     * @param $data
     * @return mixed
     */
    private function dataDeal($data){
        if(empty($data['electric'])){
            $data['electric'] = Base::ELECTRIC_ORDINARY;
        }

        $data['size'] = GoodsService::genSize($data);
        $source = [];
        if(!empty($data['source'])) {
            foreach ($data['source']['id'] as $k => $v) {
                $platform_url = empty($data['source']['platform_url'][$k]) ? '' : $data['source']['platform_url'][$k];
                $id = empty($data['source']['id'][$k]) ? '' : $data['source']['id'][$k];
                if(empty($id) && empty($platform_url)){
                    continue;
                }
                $source[] = [
                    'id' => $id,
                    'platform_url' =>$platform_url,
                    'platform_type' => empty($data['source']['platform_type'][$k]) ? '' : $data['source']['platform_type'][$k],
                    'price' => empty($data['source']['price'][$k]) ? 0 : $data['source']['price'][$k],
                ];
            }
        }
        $data['source'] = $source;
        return $data;
    }

    /**
     * @routeName 已修复商品
     * @routeDescription 已修复商品
     * @return array |Response|string
     * @throws \yii\base\Exception
     */
    public function actionGoodsPerfect()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->get('proposal_id');
        $purchase_proposal = PurchaseProposal::findOne($id);
        if(empty($purchase_proposal['sku_no'])){
            $purchase_proposal->delete();
            return $this->FormatArray(self::REQUEST_SUCCESS, "修复成功", []);
        }

        if(!empty($purchase_proposal['goods_no'])){
            return $this->FormatArray(self::REQUEST_FAIL, "该商品不需要修复", []);
        }

        $goods = GoodsChild::findOne(['sku_no'=>$purchase_proposal['sku_no']]);
        if(empty($goods)){
            return $this->FormatArray(self::REQUEST_FAIL, "未找到商品，修复失败", []);
        }

        $purchase_proposal->cgoods_no = $goods['cgoods_no'];
        $purchase_proposal->goods_no = $goods['goods_no'];
        if ($purchase_proposal->save()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "修复成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "修复失败", []);
        }
    }


    /**
     * @routeName 搁置分配
     * @routeDescription 搁置分配
     * @return array
     * @throws
     */
    public function actionShelve(){
        $req = Yii::$app->request;
        $id = $req->get('proposal_id');
        $status = $req->get('status');
        $info = PurchaseProposal::findOne($id);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $model = PurchaseProposal::findOne($post['id']);
            $model['remarks'] = $post['remarks'];
            $status = $post['status'];
            if ($status != PurchaseProposal::NORMAL_STATUS){
                if ($model['shelve_status'] != $status) {
                    $model['shelve_status'] = $status;
                    if ($model->save()) {
                        return $this->FormatArray(self::REQUEST_SUCCESS, "移入".PurchaseProposal::$shelve_status_maps[$status]."成功", []);
                    } else {
                        return $this->FormatArray(self::REQUEST_FAIL, "移入".PurchaseProposal::$shelve_status_maps[$status]."失败", []);
                    }
                }
            }
            $model['shelve_status'] = PurchaseProposal::NORMAL_STATUS;
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "取消成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, "取消失败", []);
            }
        }else{
            return $this->render('shelve_remarks',['info'=>$info,'status'=>$status]);
        }
    }


    /**
     * @routeName 备注
     * @routeDescription 备注
     * @return array
     * @throws
     */
    public function actionRemakes(){
        $req = Yii::$app->request;
        $id = $req->get('proposal_id');
        $info = PurchaseProposal::findOne($id);
        if ($req->isPost){
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $model = PurchaseProposal::findOne($post['id']);
            $model['remarks'] = $post['remarks'];
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "备注成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, "备注失败", []);
            }
        }else{
            return $this->render('remakes',['info'=>$info]);
        }
    }

    /**
     * @routeName 批量分配
     * @routeDescription 批量分配
     * @return array
     * @throws
     */
    public function actionBatchAllo()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            if(empty($post['admin_id'])) {
                return $this->FormatArray(self::REQUEST_FAIL, "平台商品数据处理员不能为空", []);
            }

            $purchase = PurchaseProposal::find()->where(['id' => explode(',', $id)])->all();
            if(empty($purchase)) {
                return $this->FormatArray(self::REQUEST_FAIL, "分配商品不能为空", []);
            }
            foreach ($purchase as $v) {
                try {
                    $v['admin_id'] = (int)$post['admin_id'];
                    $re = $v->save();
                } catch (\Exception $e) {
                    CommonUtil::logs($v['goods_no'].' 分配失败 '.$e->getMessage(),'purchase_batch_allo');
                }
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "分配成功", []);
        } else {
            $admin_ids = AccessService::getPurchaseUserIds();
            $admin_lists = AdminUser::find()->where(['id' => $admin_ids])->andWhere(['=','status',AdminUser::STATUS_ACTIVE])
                ->select(['id','nickname'])->asArray()->all();
            $admin_lists = ArrayHelper::map($admin_lists,'id','nickname');
            return $this->render('allo', ['admin_lists' => $admin_lists]);
        }
    }


    /**
     * @routeName 批量移入
     * @routeDescription 批量移入
     * @return array
     * @throws
     */
    public function actionShelveBatchAllo(){
        $req = Yii::$app->request;
        $id = $req->get('id');
        $status = $req->get('status');
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $remarks = $post['remarks'];
            $list = [];
            if(!empty($id)) {
                $list = PurchaseProposal::find()->where(['id' => explode(',', $id)])->select('id')->all();
            }
            foreach ($list as $v){
                $purchase_id = $v['id'];
                $model = PurchaseProposal::findOne($purchase_id);
                if ($model['shelve_status'] == $status){
                    return $this->FormatArray(self::REQUEST_FAIL, "移入失败,已经存在".PurchaseProposal::$shelve_status_maps[$status], []);
                }
                $model['remarks'] = $remarks;
                $model['shelve_status'] = $status;
                if ($model->save()){
                }else {
                    return $this->FormatArray(self::REQUEST_FAIL, "移入失败", []);
                }
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "移入成功", []);
        }else{
            return $this->render('shelve_batch_allo');
        }
    }

    /**
     * @routeName 批量取消
     * @routeDescription 批量取消
     * @return array
     * @throws
     */
    public function actionShelveNormalBatchAllo(){
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $remarks = $post['remarks'];
            $list = [];
            if(!empty($id)) {
                $list = PurchaseProposal::find()->where(['id' => explode(',', $id)])->select(['id','shelve_status'])->asArray()->all();
            }
            foreach ($list as $v){
                $purchase_id = $v['id'];
                $model = PurchaseProposal::findOne($purchase_id);
                if ($model['shelve_status'] == PurchaseProposal::NORMAL_STATUS){
                    return $this->FormatArray(self::REQUEST_FAIL, "取消失败,已经在正常件", []);
                }
                $model['remarks'] = $remarks;
                $model['shelve_status'] = PurchaseProposal::NORMAL_STATUS;
                if ($model->save()){
                }else {
                    return $this->FormatArray(self::REQUEST_FAIL, "取消失败", []);
                }
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "取消成功", []);
        }else{
            return $this->render('shelve_normal_batch_allo');
        }
    }

    /**
     * @routeName 删除
     * @routeDescription 删除
     * @return array
     * @throws
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('proposal_id');
        $model = PurchaseProposal::findOne($id);
        if ($model->delete()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除失败", []);
        }
    }


}