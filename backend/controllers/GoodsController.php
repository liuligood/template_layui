<?php

namespace backend\controllers;

use backend\models\AdminUser;
use backend\models\search\GoodsSearch;
use backend\models\search\OrderSearch;
use backend\models\search\PurchaseOrderSearch;
use common\components\CommonUtil;
use common\components\HelperStamp;
use common\components\statics\Base;
use common\models\Attachment;
use common\models\BaseAR;
use common\models\Category;
use common\models\CategoryMapping;
use common\models\CategoryProperty;
use common\models\CategoryPropertyValue;
use common\models\FindGoods;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsDistributionWarehouse;
use common\models\goods\GoodsExtend;
use common\models\goods\GoodsImages;
use common\models\goods\GoodsLanguage;
use common\models\goods\GoodsStock;
use common\models\goods\GoodsStockDetails;
use common\models\goods\GoodsStockLog;
use common\models\goods\GoodsTranslate;
use common\models\goods\OriginalGoodsName;
use common\models\goods\OverseasGoods;
use common\models\GoodsAdditional;
use common\models\GoodsAttribute;
use common\models\GoodsProperty;
use common\models\goods\GoodsPackaging;
use common\models\GoodsSelection;
use common\models\GoodsShop;
use common\models\GoodsSource;
use common\models\IndependenceCategory;
use common\models\Order;
use common\models\OrderGoods;
use common\models\OrderStockOccupy;
use common\models\platform\PlatformCategory;
use common\models\PlatformInformation;
use common\models\purchase\PurchaseOrder;
use common\models\Shop;
use common\models\Supplier;
use common\models\SupplierRelationship;
use common\models\sys\FrequentlyOperations;
use common\models\sys\SystemOperlog;
use common\models\User;
use common\models\warehousing\BlContainer;
use common\models\warehousing\BlContainerGoods;
use common\models\warehousing\BlContainerTransportation;
use common\models\warehousing\Warehouse;
use common\models\warehousing\WarehouseProductSales;
use common\models\warehousing\WarehouseProvider;
use common\services\goods\EEditorService;
use common\services\goods\GoodsLockService;
use common\services\goods\GoodsService;
use common\services\goods\GoodsStockService;
use common\services\goods\GoodsTranslateService;
use common\services\goods\OverseasGoodsService;
use common\services\goods\FindGoodsService;
use common\services\ImportResultService;
use common\services\purchase\PurchaseOrderService;
use common\services\purchase\PurchaseProposalService;
use common\services\ShopService;
use common\services\sys\AccessService;
use common\services\sys\CountryService;
use common\services\sys\ExportService;
use common\services\sys\FrequentlyOperationsService;
use common\services\sys\SystemOperlogService;
use common\services\warehousing\WarehouseService;
use moonland\phpexcel\Excel;
use Yii;
use common\base\BaseController;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use common\models\goods\GoodsReason;


class GoodsController extends BaseController
{

    protected $render_view = '/goods/goods/';

    /**
     * 首页列表
     * @param $tag
     * @param $source_method
     * @return string
     */
    protected function _index($tag,$source_method,$render_view = 'index')
    {
        $req = Yii::$app->request;
        $tag = $req->get('tag',$tag);
        $source_method = $req->get('source_method',$source_method);
        if($tag == 3){
            $searchModel = new GoodsSearch();
            $where = $searchModel->search([], $tag,$source_method);
            $category_cuts = Goods::find()->where($where)->select('category_id,count(*) cut')->groupBy('category_id')->asArray()->all();
            $category_id = ArrayHelper::getColumn($category_cuts,'category_id');
            $category_cuts = ArrayHelper::map($category_cuts,'category_id','cut');
            $category_lists = Category::find()->select('name,id')->where(['source_method'=>$source_method,'id'=>$category_id])->asArray()->all();
            $category_arr  =[];
            foreach ($category_lists as $v){
                $category_arr[$v['id']] = $v['name'] . '（'.$category_cuts[$v['id']].'）';
            }
        }else {
            //$category_arr = Category::getCategoryOptCache($source_method);
            $category_arr = [];
            //$category_arr = Category::getChildOpt($source_method);
            //array_unshift($category_arr, ['name' => '未设置类目', 'id' => -1, 'parent_id' => 0, 'value' => '-1']);
        }
        $source_platform_category_arr  =[];
        $source_platform_title_arr  =[];
        /*if($tag == 4){
            $searchModel = new GoodsSearch();
            $where = $searchModel->search([], $tag,$source_method);
            $category_cuts = Goods::find()->where($where)->select('source_platform_category_id,source_platform_category_name,count(*) cut')->groupBy('source_platform_category_id')->asArray()->all();
            foreach ($category_cuts as $v){
                $source_platform_category_arr[$v['source_platform_category_id']] = $v['source_platform_category_name'] . '（'.$v['cut'].'）';
            }

            $category_cuts = Goods::find()->where($where)->select('source_platform_title,count(*) cut')->groupBy('source_platform_title')->asArray()->all();
            foreach ($category_cuts as $v){
                $source_platform_title_arr[$v['source_platform_title']] = $v['source_platform_title'] . '（'.$v['cut'].'）';
            }
        }*/
        $shop_arr = ShopService::getShopDropdown();
        $admin_lists = AdminUser::find()->where(['id' => AccessService::getGoodsSupplementUserIds()])->andWhere(['=','status',AdminUser::STATUS_ACTIVE])->select(['id','nickname','username'])->asArray()->all();
        $owner_admin_lists = AdminUser::find()->where(['id' => AccessService::getShopOperationUserIds()])->andWhere(['=','status',AdminUser::STATUS_ACTIVE])->select(['id','nickname','username'])->asArray()->all();
        $admins = [];
        foreach ($admin_lists as $admin_v){
            $admins[$admin_v['id']] = $admin_v['nickname'] .'('.$admin_v['username'].')';
        }
        $owner_admins = [];
        foreach ($owner_admin_lists as $admin_v){
            $owner_admins[$admin_v['id']] = $admin_v['nickname'] .'('.$admin_v['username'].')';
        }
        $warehouse_lists = [
            'warehouse_id' => 0,
            'data' => []
        ];
        if ($tag == 7) {
            $warehouse_id = $req->get('warehouse_id');
            $warehouse_provider = WarehouseProvider::find()->where(['warehouse_provider_type' => WarehouseProvider::TYPE_B2B])->select('id')->asArray()->all();
            $warehouse_provider_id = ArrayHelper::getColumn($warehouse_provider,'id');
            $model = Warehouse::find()->where(['warehouse_provider_id' => $warehouse_provider_id])->select(['id','warehouse_name'])->asArray()->all();
            $warehouse_id = empty($warehouse_id) ? $model[0]['id'] : $warehouse_id;
            $warehouse_lists = [
                'warehouse_id' => $warehouse_id,
                'data' => $model
            ];
        }
        return $this->render($this->render_view.$render_view,[
            'tag'=>$tag,
            'goods_stamp_tag' => $req->get('goods_stamp_tag'),
            'goods_tort_type' => $req->get('goods_tort_type',0),
            'source_method'=>$source_method,
            'category_arr'=>$category_arr,
            'source_platform_category_arr' => $source_platform_category_arr,
            'source_platform_title_arr' => $source_platform_title_arr,
            'shop_arr' => $shop_arr,
            'admin_arr' => $admins,
            'owner_admin_arr' => $owner_admins,
            'all_goods_access' => AccessService::hasAllGoods(),
            'warehouse_lists' => $warehouse_lists
        ]);
    }

    /**
     * @routeName 平台商品分配
     * @routeDescription 平台商品分配
     */
    public function actionAlloList()
    {
        return $this->_index(3,GoodsService::SOURCE_METHOD_OWN);
    }

    /**
     * @routeName 采集商品管理
     * @routeDescription 采集商品管理
     */
    public function actionGrabList()
    {
        return $this->_index(1,GoodsService::SOURCE_METHOD_OWN);
    }

    /**
     * @routeName 平台商品库
     * @routeDescription 平台商品库
     */
    public function actionIndex()
    {
        return $this->_index(2,GoodsService::SOURCE_METHOD_OWN);
    }

    /**
     * @routeName 平台商品配对
     * @routeDescription 平台商品配对
     */
    public function actionMatchList()
    {
        return $this->_index(4,GoodsService::SOURCE_METHOD_OWN);
    }

    /**
     * @routeName 平台商品配对(精)
     * @routeDescription 平台商品配对(精)
     */
    public function actionFineMatchList()
    {
        return $this->_index(6,GoodsService::SOURCE_METHOD_OWN);
    }

    /**
     * @routeName 平台商品(精)
     * @routeDescription 平台商品(精)
     */
    public function actionFineIndex()
    {
        return $this->_index(5, GoodsService::SOURCE_METHOD_OWN);
    }

    /**
     * @routeName 分销商品
     * @routeDescription 分销商品
     */
    public function actionDistributionIndex()
    {
        return $this->_index(7, GoodsService::SOURCE_METHOD_OWN,'distribution_index');
    }

    /**
     * @routeName 亚马逊商品库
     * @routeDescription 亚马逊商品库
     */
    public function actionAmazonIndex()
    {
        return $this->_index(2,GoodsService::SOURCE_METHOD_AMAZON);
    }

    /**
     * @routeName 简易查询列表
     * @routeDescription 简易查询列表
     */
    public function actionSimpleIndex()
    {
        return $this->_index(10,3,'simple_index');
    }

    /**
     * @routeName 选择商品列表
     * @routeDescription 选择商品列表
     */
    public function actionSelectIndex()
    {
        $req = Yii::$app->request;
        $tag = $req->get('tag');
        $sub_tag = $req->get('sub_tag',0);
        return $this->render($this->render_view.'select_index',[
            'tag'=>$tag,
            'sub_tag' => $sub_tag,
        ]);
    }

    public function model(){
        return new Goods();
    }

    /**
     * @routeName 商品列表
     * @routeDescription 商品列表
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $req = Yii::$app->request;
        $tag = $req->get('tag');
        $source_method = $req->get('source_method');
        $goods_stamp_tag = $req->get('goods_stamp_tag');
        $goods_tort_type = $req->get('goods_tort_type');
        $searchModel = new GoodsSearch();
        $searchModel->goods_stamp_tag = $goods_stamp_tag;
        $searchModel->goods_tort_type = $goods_tort_type;
        $query_params = Yii::$app->request->post();
        $lists = [];
        $total_count = 0;
        if(!empty($query_params['GoodsSearch']) || $tag != 10) {
            $where = $searchModel->search($query_params, $tag, $source_method);
            //$data = $this->lists($where,$tag == 1?'add_time asc':'add_time desc');
            $this->substep_query = true;
            $data = $this->lists($where, $tag == 1 || $tag == 2 ? 'id asc' : 'add_time desc');
            $total_count = $data['pages']->totalCount;
            //$goods_nos = ArrayHelper::getColumn($data['list'], 'goods_no');
            //$goods_allegro = GoodsAllegro::find()->where(['goods_no' => $goods_nos])->indexBy('goods_no')->asArray()->all();

            foreach ($data['list'] as $v) {
                $one = OriginalGoodsName::find()->where(['goods_no'=>$v['goods_no']])->asArray()->all();
                $info = $v;
                $info['count'] = count($one);
                $image = json_decode($v['goods_img'], true);
                $info['image'] = empty($image) || !is_array($image) ? '' : current($image)['img'];
                $info['add_time'] = date('Y-m-d H:i', $v['add_time']);
                $info['update_time'] = date('Y-m-d H:i', $v['update_time']);
                $info['status_desc'] = empty(Goods::$status_map[$v['status']]) ? '' : Goods::$status_map[$v['status']];
                $info['stock_desc'] = empty(Goods::$stock_map[$v['stock']]) ? '' : Goods::$stock_map[$v['stock']];
                //$info['claim'][Base::PLATFORM_ALLEGRO] = empty($goods_allegro[$v['goods_no']]) ? 0 : 1;
                $user = User::getInfo($info['admin_id']);
                $owner_user = User::getInfo($info['owner_id']);
                $info['owner_name'] = empty($owner_user['nickname']) ? '' : $owner_user['nickname'];
                $info['admin_name'] = empty($user['nickname']) ? '' : $user['nickname'];
                $info['category_name'] = Category::getCategoryName($v['category_id']).'('.$v['category_id'].')';
                $source_platforms = GoodsService::getGoodsSource($v['source_method']);
                $info['source_platform_type'] = empty($source_platforms[$v['source_platform_type']]) ? '' : $source_platforms[$v['source_platform_type']];
                $goods_tort_type_map = GoodsService::getGoodsTortTypeMap($v['source_method_sub']);
                $info['goods_tort_type_desc'] = empty($goods_tort_type_map[$v['goods_tort_type']])?'':$goods_tort_type_map[$v['goods_tort_type']];
                //$info['goods_content'] = htmlspecialchars($info['goods_content']);
                //$info['source_platform_category_desc'] = $info['source_platform_category_name'] . (empty($info['source_platform_category_id'])?'':('('.$info['source_platform_category_id'].')'));
                $lists[] = $info;
            }
        }

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $total_count
        );
    }


    /**
     * @routeName 分销商品列表
     * @routeDescription 分销商品列表
     */
    public function actionDistributionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $req = Yii::$app->request;
        $tag = $req->get('tag');
        $source_method = $req->get('source_method');
        $goods_stamp_tag = $req->get('goods_stamp_tag');
        $goods_tort_type = $req->get('goods_tort_type');
        $warehouse_id = $req->get('warehouse');
        $searchModel = new GoodsSearch();
        $searchModel->goods_stamp_tag = $goods_stamp_tag;
        $searchModel->goods_tort_type = $goods_tort_type;
        $query_params = Yii::$app->request->post();
        $lists = [];
        $where = $searchModel->distribution_search($query_params, $source_method, $warehouse_id);
        $this->substep_query = true;
        $data = $this->distribution_lists($where);

        foreach ($data['list'] as $v) {
            $one = OriginalGoodsName::find()->where(['goods_no'=>$v['goods_no']])->asArray()->all();
            $info = $v;
            $info['count'] = count($one);
            $image = json_decode($v['goods_img'], true);
            $info['image'] = empty($image) || !is_array($image) ? '' : current($image)['img'];
            $info['add_time'] = date('Y-m-d H:i', $v['add_time']);
            $info['status_desc'] = empty(Goods::$status_map[$v['status']]) ? '' : Goods::$status_map[$v['status']];
            $info['stock_desc'] = empty(Goods::$stock_map[$v['stock']]) ? '' : Goods::$stock_map[$v['stock']];
            $info['category_name'] = Category::getCategoryName($v['category_id']).'('.$v['category_id'].')';
            $source_platforms = GoodsService::getGoodsSource($v['source_method']);
            $info['source_platform_type'] = empty($source_platforms[$v['source_platform_type']]) ? '' : $source_platforms[$v['source_platform_type']];
            $goods_tort_type_map = GoodsService::getGoodsTortTypeMap($v['source_method_sub']);
            $info['goods_tort_type_desc'] = empty($goods_tort_type_map[$v['goods_tort_type']])?'':$goods_tort_type_map[$v['goods_tort_type']];
            //$info['goods_content'] = htmlspecialchars($info['goods_content']);
            //$info['source_platform_category_desc'] = $info['source_platform_category_name'] . (empty($info['source_platform_category_id'])?'':('('.$info['source_platform_category_id'].')'));
            $lists[] = $info;
        }

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $data['pages']->totalCount
        );
    }

    /**
     * @routeName 商品列表
     * @routeDescription 商品列表
     */
    public function actionSelectList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $req = Yii::$app->request;
        $tag = $req->get('tag');
        $sub_tag = $req->get('sub_tag',0);
        $searchModel = new GoodsSearch();
        $query_params = Yii::$app->request->post();
        $lists = [];
        $total_count = 0;
        if(!empty($query_params['GoodsSearch'])) {
            $where = $searchModel->selectSearch($query_params, $tag);
            $this->substep_query = true;
            $data = $this->select_lists($where);
            $total_count = $data['pages']->totalCount;

            $cgoods_no = ArrayHelper::getColumn($data['list'],'cgoods_no');
            $exist_cgods_no = [];
            if($tag == 1 && $sub_tag != 0) {
                $exist_cgods_no = GoodsStock::find()->where(['warehouse'=>$sub_tag,'cgoods_no'=>$cgoods_no])->select('cgoods_no')->column();
            }

            foreach ($data['list'] as $v) {
                $info = $v;
                $image = json_decode($v['goods_img'], true);
                $info['goods_img'] = !empty($v['img'])?$v['img']:(empty($image) || !is_array($image) ? '' : current($image)['img']);
                $info['status_desc'] = empty(Goods::$status_map[$v['status']]) ? '' : Goods::$status_map[$v['status']];
                $info['stock_desc'] = empty(Goods::$stock_map[$v['stock']]) ? '' : Goods::$stock_map[$v['stock']];
                $info['category_name'] = Category::getCategoryName($v['category_id']).'('.$v['category_id'].')';
                $source_platforms = GoodsService::getGoodsSource($v['source_method']);
                $info['source_platform_type'] = empty($source_platforms[$v['source_platform_type']]) ? '' : $source_platforms[$v['source_platform_type']];
                $goods_tort_type_map = GoodsService::getGoodsTortTypeMap($v['source_method_sub']);
                $info['goods_tort_type_desc'] = empty($goods_tort_type_map[$v['goods_tort_type']])?'':$goods_tort_type_map[$v['goods_tort_type']];
                $info['exist'] = in_array($v['cgoods_no'],$exist_cgods_no)?1:0;
                $lists[] = $info;
            }
        }

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $total_count
        );
    }

    public function select_query($type = 'select')
    {
        $select = 'g.*,gc.cgoods_no,gc.sku_no,gc.colour as ccolour,gc.size as csize,gc.price,gc.goods_img as img,gc.weight,gc.real_weight,gc.gbp_price,gc.size,gc.id';
        if ($type == 'count') {
            $select = 'gc.id';
        }
        $query = $this->model()->find()
            ->alias('g')->select($select);
        $query->leftJoin(GoodsChild::tableName() . ' gc', 'g.goods_no = gc.goods_no');
        return $query;
    }

    public function distribution_query($type = 'select')
    {
        $select = 'g.*,sum(gs.num) as warehouse_num';
        $query = GoodsStock::find()->alias('gs')->select($select);
        $query->leftJoin(GoodsChild::tableName().' gc','gc.cgoods_no = gs.cgoods_no');
        $query->leftJoin(Goods::tableName().' g','g.goods_no = gc.goods_no');
        $query->groupBy('g.goods_no');
        return $query;
    }

    /**
     * 分销列表
     * @param $where
     * @param string $sort
     * @return array
     */
    protected function distribution_lists($where, $sort = null)
    {
        $page = Yii::$app->request->get('page');
        if(empty($page)) {
            $page = Yii::$app->request->post('page',1);
        }
        $pageSize = Yii::$app->request->get('limit');
        if(empty($pageSize)) {
            $pageSize = Yii::$app->request->post('limit',20);
        }
        $model = $this->model();
        if (!($model instanceof BaseAR)) {
            return [];
        }

        $query = $this->distribution_query();
        $list = $model::getListByCond($where, $page, $pageSize, $sort,null,$query);
        if(count($list) < $pageSize && $page == 1) {
            $count = count($list);
        } else {
            if($this->cache_count) {
                $count = $model::getCacheCountByCond($where, $this->distribution_query('count'), __CLASS__ . __FUNCTION__);
            } else {
                $count = $model::getCountByCond($where, $this->distribution_query('count'));
            }
        }
        $pages = new Pagination(['totalCount' => $count, 'pageSize' => $pageSize]);
        $list = $this->formatLists($list);

        return [
            'list' => $list,
            'pages' => $pages,
        ];
    }

    /**
     * 列表
     * @param $where
     * @param string $sort
     * @return array
     */
    protected function select_lists($where, $sort = null)
    {
        $page = Yii::$app->request->get('page');
        if(empty($page)) {
            $page = Yii::$app->request->post('page',1);
        }
        $pageSize = Yii::$app->request->get('limit');
        if(empty($pageSize)) {
            $pageSize = Yii::$app->request->post('limit',20);
        }
        $model = $this->model();
        if (!($model instanceof BaseAR)) {
            return [];
        }

        $query = $this->select_query();
        $list = $model::getListByCond($where, $page, $pageSize, $sort,null,$query);
        if(count($list) < $pageSize && $page == 1) {
            $count = count($list);
        } else {
            if($this->cache_count) {
                $count = $model::getCacheCountByCond($where, $this->select_query('count'), __CLASS__ . __FUNCTION__);
            } else {
                $count = $model::getCountByCond($where, $this->select_query('count'));
            }
        }
        $pages = new Pagination(['totalCount' => $count, 'pageSize' => $pageSize]);
        $list = $this->formatLists($list);

        return [
            'list' => $list,
            'pages' => $pages,
        ];
    }

    /**
     * @routeName 锁定价格
     * @routeDescription 锁定价格
     */
    public function actionLockPrice()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $goods_no = $req->get('goods_no');
        $checked = $req->get('checked');
        if (!$checked) {
            $msg = '解锁';
            $result = GoodsLockService::unlockPrice($goods_no);
        } else {
            $msg = '锁定';
            $result = GoodsLockService::lockPrice($goods_no);
        }
        if ($result) {
            return $this->FormatArray(self::REQUEST_SUCCESS, $msg . "成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, $msg . "失败", []);
        }
    }

    /**
     * @routeName 新增商品
     * @routeDescription 创建新的商品
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $source_method_sub = $req->get('source_method_sub',0);
        $goods_model = new Goods();
        $selection_id = $req->get('selection_id');
        if($req->isPost){
            $selection_id=$req->post('selection_id');
        }
        $goods_source = [];
        if (!empty($selection_id)){
        $list = GoodsSelection::find()->where(['id' => $selection_id])->select(['id', 'platform_type', 'platform_url', 'goods_img', 'category_id', 'status', 'goods_type', 'platform_title'])->asArray()->one();
        $goods_model['goods_img'] = $list['goods_img'];
        $goods_model['category_id'] = $list['category_id'];
        $goods_model['goods_type'] = $list['goods_type'];
        $goods_model['goods_name_cn'] = $list['platform_title'];
        $goods_source['platform_url'] = $list['platform_url'];
        $goods_source['platform_type'] = $list['platform_type'];
        $goods_source = [$goods_source];
        $status = $list['status'];
        if ($status == 2) {
            throw new NotFoundHttpException('该商品已经生成');
        }
        }
        if ($req->isPost) {
            $goods_name = $req->post('goods_name');
            $goods_name_old = $req->post('goods_name_old');
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $source_method_sub = $req->post('source_method_sub');
            $data = $this->dataDeal($post);
            if (isset($data['currency'])){
                $currency = $data['currency'];
                FrequentlyOperationsService::addOperation(FrequentlyOperations::TYPE_CURRENCY,$currency);
            }
            try {
                if ($goods_no = (new GoodsService())->addGoods($data)) {
                    if($goods_name!=$goods_name_old){
                        $model = new OriginalGoodsName();
                        $model->goods_name = $goods_name_old;
                        $model->goods_no = $goods_no;
                        $model->save();
                    }
                    if (!empty($selection_id)){
                        $list = GoodsSelection::findOne(['id' => $selection_id]);
                        $list->status = 2;
                        $list->goods_no = $goods_no;
                        $list->save();
                    }
                    if (!empty($post['additional_video']) || !empty($post['additional_tk_video'])){
                        $additional_model = GoodsAdditional::findOne(['goods_no'=>$goods_no]);
                        if (empty($additional_model)){
                            $additional_model = new GoodsAdditional();
                        }
                        $additional_model->video = $post['additional_video'];
                        $additional_model->goods_no = $goods_no;
                        $additional_model->tk_video = $post['additional_tk_video'];
                        $additional_model->save();
                    }
                    if (!empty($post['attribute_value'])) {
                        $attribute_property['goods_no'] = $goods_no;
                        $attribute_property['attribute_value'] = $post['attribute_value'];
                        $attribute_property['attribute_value_custom'] = $post['attribute_value_custom'];
                        (new GoodsService())->createGoodsProperty($attribute_property);
                    }
                    return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, "添加失败", []);
                }
            }catch (\Exception $e){
                return $this->FormatArray(self::REQUEST_FAIL, $e->getMessage(), []);
            }
        }
        $frequently_operation = FrequentlyOperationsService::getOperation(FrequentlyOperations::TYPE_CATEGORY,3);
        $frequently_operation_currency = FrequentlyOperationsService::getOperation(FrequentlyOperations::TYPE_CURRENCY,1);
        $frequently_operation_list = [];
        foreach ($frequently_operation as $v) {
            $name = Category::getCategoryNamesTreeByCategoryId($v,' / ');
            $frequently_operation_list[$v] = $name;
        }
        $goods_model['source_method'] = GoodsService::SOURCE_METHOD_OWN;
        $goods_model['source_method_sub'] = $source_method_sub;
        $goods_model['currency'] = $frequently_operation_currency;
        $is_fine_goods = $source_method_sub == 0?0:1;
        return $this->render($this->render_view.'update',['goods' => $goods_model,'attribute'=>'','is_fine_goods'=>$is_fine_goods,
            'has_multi' => $is_fine_goods == 1,'frequently_operation'=>$frequently_operation_list,'has_gbp_price' => false,'source'=>json_encode($goods_source),'selection_id'=>$selection_id]);
    }

    /**
     * @routeName 采集商品
     * @routeDescription 采集商品
     * @throws
     * @return string |Response |array
     */
    public function actionGrab()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            try {
                $urls = explode("\n",$post['url']);
                $success = 0;
                $fail = 0;
                foreach ($urls as $v){
                    $v = trim($v);
                    if(empty($v)){
                        continue;
                    }
                    if ((new GoodsService())->grab($v,\Yii::$app->user->identity->id)){
                        $success++;
                    }else{
                        $fail++;
                    }
                }
                return $this->FormatArray(self::REQUEST_SUCCESS, "采集成功{$success}条，失败{$fail}条", []);
                /*if ((new GoodsService())->grab($post['url'])) {
                    return $this->FormatArray(self::REQUEST_SUCCESS, "采集成功", []);
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, '采集失败', []);
                }*/
            }catch (\Exception $e){
                return $this->FormatArray(self::REQUEST_FAIL, '采集失败'.$e->getMessage(), []);
            }
        }
        return $this->render($this->render_view.'grab');
    }

    /**
     * @routeName 更新商品
     * @routeDescription 更新商品信息
     * @throws
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $ogid = $req->get('ogid');
        $aid = $req->get('aid');
        $selection_id = $req->get('selection_id');
        if ($req->isPost) {
            $id = $req->post('id');
            $ogid = $req->post('ogid');
            $aid = $req->post('aid');
            $goods_name_old = $req->post('goods_name_old');
        }
        if ($req->isPost) {
            $goods_model = $this->findModel($id);
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $data = $this->dataDeal($post);
            if (empty($data['category_id'])) {
                return $this->FormatArray(self::REQUEST_FAIL, '分类不能为空', []);
            }
            try {
                if ((new GoodsService())->updateGoods($id, $data)) {
                    if($goods_model['goods_name']!=$goods_name_old){
                        $goods_no = $goods_model['goods_no'];
                        $one = OriginalGoodsName::find()->where(['goods_no'=>$goods_no])->asArray()->all();
                        if(count($one)==0){
                        $model = new OriginalGoodsName();
                        $model->goods_no = $goods_no;
                        $model->goods_name = $goods_name_old;
                        $model->save();}
                    }
                    if (!empty($aid)){
                        FindGoods::updateOneByCond(['id'=>$ogid],['overseas_goods_status'=>FindGoods::FIND_GOODS_STATUS_NORMAL]);
                    }elseif (!empty($ogid)){
                        OverseasGoods::updateOneByCond(['id'=>$ogid],['overseas_goods_status'=>OverseasGoods::OVERSEAS_GOODS_STATUS_NORMAL]);
                    }
                    $additional_model = GoodsAdditional::findOne(['goods_no'=>$post['goods_no']]);
                    if (!empty($post['additional_video']) || !empty($post['additional_tk_video']) || !empty($additional_model)){
                        if (empty($additional_model)){
                            $additional_model = new GoodsAdditional();
                        }
                        $additional_model->video = $post['additional_video'];
                        $additional_model->goods_no = $post['goods_no'];
                        $additional_model->tk_video = $post['additional_tk_video'];
                        $additional_model->save();
                    }
                    return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, "更新失败", []);
                }
            }catch (\Exception $e){
                return $this->FormatArray(self::REQUEST_FAIL, $e->getMessage(), []);
            }
        } else {
            $has_gbp_price = false;
            $goods_model = $this->findModel($id);
            $goods_additional = GoodsAdditional::find()->where(['goods_no'=>$goods_model['goods_no']])->asArray()->one();
            $goods_source = GoodsSource::find()->alias('gs')
                ->leftJoin(Supplier::tableName().' s','gs.supplier_id = s.id')
                ->select('gs.*,s.url')
                ->where(['goods_no' => $goods_model['goods_no']])->asArray()->all();
            $goods_attribute = GoodsAttribute::find()->where(['goods_no' => $goods_model['goods_no']])->asArray()->all();
            $distribution_warehouse = GoodsDistributionWarehouse::find()->where(['goods_no'=>$goods_model['goods_no']])->select('warehouse_id')->column();
            $goods_child = GoodsChild::find()->where(['goods_no' => $goods_model['goods_no']])->asArray()->all();
            foreach ($goods_child as $k=>&$child_v) {
                if ($child_v['cgoods_no'] == $goods_model['goods_no']) {
                    unset($goods_child[$k]);
                    continue;
                }
                if($child_v['gbp_price'] > 0) {
                    $has_gbp_price = true;
                }

                $size = (new GoodsService())->getSizeArr($child_v['package_size']);
                $child_v = array_merge($child_v,$size);
            }

            if($goods_model['gbp_price'] > 0) {
                $has_gbp_price = true;
            }

            /*$category_id = Category::getParentIds($goods_model['category_id']);
            $category_id = array_reverse($category_id);
            $category_id[] = $goods_model['category_id'];
            $goods_model['category_id'] = implode(',', $category_id);*/
            $size_info = (new GoodsService())->getSizeArr($goods_model['size']);
            $is_fine_goods = 0;
            if(in_array($goods_model['status'],[Goods::GOODS_STATUS_UNCONFIRMED,Goods::GOODS_STATUS_UNALLOCATED,Goods::GOODS_STATUS_WAIT_ADDED])
            || GoodsService::isFine($goods_model['source_method_sub'])) {
                $is_fine_goods = 1;
            }
            $frequently_operation = FrequentlyOperationsService::getOperation(FrequentlyOperations::TYPE_CATEGORY,3);
            $frequently_operation_list = [];
            foreach ($frequently_operation as $v) {
                $name = Category::getCategoryNamesTreeByCategoryId($v,' / ');
                $frequently_operation_list[$v] = $name;
            }
            $goods_property = GoodsProperty::find()->where(['goods_no'=>$goods_model['goods_no']])->asArray()->all();
            return $this->render($this->render_view . 'update', ['goods' => $goods_model,
                'source' => json_encode($goods_source),
                'attribute' => json_encode($goods_attribute),
                'size' => $size_info,
                'property' => json_encode($goods_child),
                'is_fine_goods'=>$is_fine_goods,
                'distribution_warehouse' => $distribution_warehouse,
                'has_multi' => $is_fine_goods == 1 || $goods_model['goods_type'] == Goods::GOODS_TYPE_MULTI?true:false,
                'frequently_operation'=>$frequently_operation_list,
                'ogid' => $ogid,
                'aid' => $aid,
                'has_gbp_price' => $has_gbp_price,
                'selection_id' => $selection_id,
                'goods_additional'=>$goods_additional,
                'goods_property' => $goods_property
            ]);
        }
    }

    /**
     * @routeName 更新商品
     * @routeDescription 更新商品信息
     * @throws
     */
    public function actionUpdatePrice()
    {
        $req = Yii::$app->request;
        $goods_no = $req->get('goods_no');
        if ($req->isPost) {
            $goods_no = $req->post('goods_no');
        }
        $goods_model = $this->findModel(['goods_no'=>$goods_no]);
        $goods_source = GoodsSource::find()->alias('gs')
            ->leftJoin(Supplier::tableName().' s','gs.supplier_id = s.id')
            ->select('gs.*,s.url')
            ->where(['goods_no' => $goods_model['goods_no']])->asArray()->all();
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $data = $this->dataPriceDeal($post);
            if ($goods_model['source_method'] == GoodsService::SOURCE_METHOD_OWN && ($data['weight'] <= 0.2 || $data['weight'] >= 20)) {
                return $this->FormatArray(self::REQUEST_FAIL, '重量超出限制', []);
            }

            $source = empty($data['source']) ? [] : $data['source'];
            $goods_property = empty($data['goods_property']) ? [] : $data['goods_property'];

            //自建价格需要取阿里巴巴价格
            if (empty($data['price']) && $goods_model['source_method'] == GoodsService::SOURCE_METHOD_OWN) {
                $price = 0;
                //验证阿里巴巴链接
                foreach ($source as $v) {
                    if (in_array($v['platform_type'],[Base::PLATFORM_1688])) {
                        $price = $v['price'];
                    }
                }
                if (empty($price) && $price <= 3) {
                    return $this->FormatArray(self::REQUEST_FAIL, '阿里巴巴价格不能为空,或价格有误', []);
                }
                $data['price'] = $price;
            }

            //价格发生变更
            /*$has_price_change = false;
            if (!empty($data['price']) && bccomp($goods_model['price'], $data['price'], 2) != 0) {
                $has_price_change = true;
            }
            if (isset($data['gbp_price']) && bccomp($goods_model['gbp_price'], $data['gbp_price'], 2) != 0) {
                $has_price_change = true;
            }
            //重量变更
            if (!empty($data['weight']) && bccomp($goods_model['weight'], $data['weight'], 2) != 0) {
                $has_price_change = true;
            }
            //尺寸变更
            if (!empty($data['size']) && $data['size'] != $goods_model['size']) {
                $has_price_change = true;
            }*/

            if ($goods_model->load($data, '') == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }

            //正常的需要去除 采集数据类型
            if($goods_model['status'] == Goods::GOODS_STATUS_VALID) {
                $goods_model['source_method_sub'] = HelperStamp::delStamp($goods_model['source_method_sub'], Goods::GOODS_SOURCE_METHOD_SUB_GRAB);
            }

            $goods_change_data = SystemOperlogService::getModelChangeData($goods_model);
            if ($goods_model->save()) {
                (new GoodsService())->updateSource($goods_model['goods_no'], $source);
                (new GoodsService())->updateProperty($goods_model['goods_no'], $goods_property);
                /*if ($has_price_change) {
                    (new GoodsService())->updatePlatformGoods($goods_model['goods_no'], true);
                }*/
                //修改商品日志
                (new SystemOperlogService())->setType(SystemOperlog::TYPE_UPDATE)
                    ->addGoodsLog($goods_model['goods_no'],$goods_change_data,SystemOperlogService::ACTION_GOODS_PRICE_UPDATE,'');
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $goods_model->getErrorSummary(false)[0], []);
            }
        } else {
            $category_id = Category::getParentIds($goods_model['category_id']);
            $category_id = array_reverse($category_id);
            $category_id[] = $goods_model['category_id'];
            $goods_model['category_id'] = implode(',', $category_id);
            $size_info = (new GoodsService())->getSizeArr($goods_model['size']);
            $goods_child = [];
            $has_gbp_price = false;
            if($goods_model['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                $goods_child = GoodsChild::find()->where(['goods_no' => $goods_model['goods_no']])->asArray()->all();
                foreach ($goods_child as $k=>&$child_v) {
                    if ($child_v['cgoods_no'] == $goods_model['goods_no']) {
                        unset($goods_child[$k]);
                        continue;
                    }
                    if($child_v['gbp_price'] > 0) {
                        $has_gbp_price = true;
                    }

                    $size = (new GoodsService())->getSizeArr($child_v['package_size']);
                    $child_v = array_merge($child_v,$size);
                }
            }

            if($goods_model['gbp_price'] > 0) {
                $has_gbp_price = true;
            }

            return $this->render($this->render_view . 'update_price', ['goods' => $goods_model,
                'source' => json_encode($goods_source),
                'size' => $size_info,
                'goods_child' => $goods_child,
                'has_gbp_price' => $has_gbp_price
            ]);
        }
    }


    /**
     * @routeName 商品详情
     * @routeDescription 商品详情
     * @throws
     */
    public function actionView()
    {
        $req = Yii::$app->request;
        $goods_no = $req->get('goods_no');
        $warehouse = $req->get('warehouse');
        $goods_model = $this->findModel(['goods_no'=>$goods_no]);
        $goods_content = explode("\n", $goods_model['goods_content']);
        $goodsreason =  GoodsReason::find()->where(['goods_no' => $goods_model['goods_no']])->asArray()->all();
        $reason = '';
        $goods_additional = GoodsAdditional::find()->where(['goods_no'=>$goods_no])->one();
        foreach ($goodsreason as $a){ if(!empty($a['reason'])){$reason ='【'.Goods::$reason_map[$a['reason']].'】'.$a['remarks'];}}
        $per_info= SystemOperlog::find()->where(['object_no' => $goods_model['goods_no']])->orderBy('id desc')->asArray()->all();
        $goods_source = GoodsSource::find()->alias('gs')
            ->leftJoin(Supplier::tableName(). ' s','gs.supplier_id = s.id')
            ->leftJoin(Warehouse::tableName(). ' w','gs.supplier_id = w.id')
            ->select('gs.*,s.name as supplier_name,w.warehouse_name')
            ->where(['goods_no' => $goods_model['goods_no']])->asArray()->all();
        $goods_child = [];
        if($goods_model['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            $goods_child = GoodsChild::find()->where(['goods_no' => $goods_model['goods_no']])->asArray()->all();
        }
        if (!empty($warehouse)) {
            $warehouse = Warehouse::find()->where(['id' => $warehouse])->select('warehouse_name')->scalar();
        }
        $category_property = GoodsService::getCategoryProperty($goods_model['category_id'],$goods_no);
        if (empty($category_property)) {
            $category_ids = Category::getParentIds($goods_model['category_id']);
            foreach ($category_ids as $k => $v) {
                $category_property =  GoodsService::getCategoryProperty($v,$goods_no);
                if (!empty($parent_category_property)) {
                   break;
                }
            }
        }
        return $this->render($this->render_view . 'view', ['goods' => $goods_model,
        	'reason' =>$reason,
            'source' => $goods_source,
            'goods_child' => $goods_child,
        	'per_info'=>$per_info,
            'goods_content' => $goods_content,
            'goods_additional'=>$goods_additional,
            'warehouse' => $warehouse,
            'category_property' => $category_property
        ]);
    }

    /**
     * @routeName 审查商品
     * @routeDescription 审查商品
     * @throws
     */
    public function actionExamine()
    {
        $cache_token_key = 'com::goods::examine::prev::' . \Yii::$app->user->identity->id;
        $cache_token_next_key = 'com::goods::examine::next::' . \Yii::$app->user->identity->id;
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $tag = $req->get('tag');
            $source_method = $req->get('source_method');
            $goods_stamp_tag = $req->get('goods_stamp_tag');
            $goods_tort_type = $req->get('goods_tort_type');
            $post = $req->post();
            if (!isset($post['goods_tort_type'])) {
                return $this->FormatArray(self::REQUEST_FAIL, "归类必须选择", []);
            }
            $goods_no = $post['goods_no'];
            try {
                if (!(new GoodsService())->examineGoods($goods_no, $post)) {
                    return $this->FormatArray(self::REQUEST_FAIL, "归类失败", []);
                }
                //成功删除缓存
                \Yii::$app->redis->setex($cache_token_key,  60 * 60, $goods_no);
                $next_goods_no = \Yii::$app->redis->get($cache_token_next_key);
                if(!empty($next_goods_no)) {//删除商品
                    $next_goods_no = json_decode($next_goods_no);
                    $key = array_search($next_goods_no, $goods_no);
                    array_splice($next_goods_no, $key, 1);
                    \Yii::$app->redis->setex($cache_token_next_key, 60 * 60, json_encode($next_goods_no));
                    //$next_goods_no = array_diff([$goods_no],$next_goods_no);
                }
            } catch (\Exception $e) {
                return $this->FormatArray(self::REQUEST_FAIL, $e->getMessage(), []);
            }
            $uri = 'tag='.$tag.'&source_method='.$source_method.'&goods_stamp_tag='.$goods_stamp_tag.'&goods_tort_type='.$goods_tort_type;
            $uri = Url::to(['goods/examine?'.$uri]);
            return $this->FormatArray(self::REQUEST_SUCCESS, "归类成功", ['url'=>$uri]);
        } else {
            $prev_goods_no = '';
            $tag = $req->get('tag');
            $goods_no = $req->get('goods_no');
            $source_method = $req->get('source_method');
            $goods_stamp_tag = $req->get('goods_stamp_tag');
            $goods_tort_type = $req->get('goods_tort_type');
            if(empty($goods_no)) {
                $next_goods_no = \Yii::$app->redis->get($cache_token_next_key);
                $next_goods_no = json_decode($next_goods_no);
                if(!empty($next_goods_no)) {
                    $prev_goods_no = \Yii::$app->redis->get($cache_token_key);
                    $goods_no = current($next_goods_no);
                }
            }
            $has_all_goods = AccessService::hasAllGoods();
            if(empty($goods_no)) {
                $prev_goods_no = \Yii::$app->redis->get($cache_token_key);
                $searchModel = new GoodsSearch();
                $searchModel->goods_stamp_tag = $goods_stamp_tag;
                $searchModel->goods_tort_type = $goods_tort_type;
                $query_params = Yii::$app->request->post();
                $where = $searchModel->search($query_params, $tag, $source_method);
                $where['status'] = Goods::GOODS_STATUS_VALID;
                $where['owner_id'] = Yii::$app->user->id;
                $goods_lists = Goods::getListByCond($where, 1, $has_all_goods?1:20);
                $goods_model = current($goods_lists);
                $goods_nos = ArrayHelper::getColumn($goods_lists,'goods_no');
                if(!$has_all_goods) {
                    \Yii::$app->redis->setex($cache_token_next_key, 60 * 60, json_encode($goods_nos));
                }
            } else {
                $goods_model = Goods::find()->where(['goods_no'=>$goods_no])->one();
            }
            if (empty($goods_model)) {
                throw new NotFoundHttpException('已经是最后一条数据');
            }
            $goods_source = GoodsSource::find()->where(['goods_no' => $goods_model['goods_no']])->asArray()->all();
            $goods_child = [];
            if($goods_model['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                $goods_child = GoodsChild::find()->where(['goods_no' => $goods_model['goods_no']])->asArray()->all();
            }
            return $this->render($this->render_view . 'examine', ['goods' => $goods_model,
                'source' => $goods_source,
                'prev_goods_no' => $prev_goods_no,
                'goods_child' => $goods_child,
                'uri'=> 'tag='.$tag.'&source_method='.$source_method.'&goods_stamp_tag='.$goods_stamp_tag.'&goods_tort_type='.$goods_tort_type
            ]);
        }
    }

    /**
     * @routeName 商品订单列表
     * @routeDescription 商品订单列表
     * @throws
     */
    public function actionViewOrder()
    {
        $req = Yii::$app->request;
        $tag = $req->get('tag',10);
        $goods_no = $req->get('goods_no');
        $page = Yii::$app->request->get('page', 1);
        $pageSize = Yii::$app->request->get('limit', 20);

        $goods_model = $this->findModel(['goods_no'=>$goods_no]);
        $searchModel=new OrderSearch();
        $searchModel->platform_asin =$goods_model['sku_no'];
        $where=$searchModel->ownSearch([],$tag);
        $sort = 'date desc';
        $count = Order::getCountByCond($where);
        $list = Order::getListByCond($where, $page, $pageSize, $sort);
        $pages = new Pagination(['totalCount' => $count, 'pageSize' => $pageSize]);
        $list = $this->orderFormatLists($list);

        return $this->render($this->render_view . 'view_order', [
            'goods' => $goods_model,
            'searchModel' => $searchModel,
            'list' => $list,
            'pages' => $pages,
            'tag'=>$tag,
        ]);
    }

    /**
     * 格式化列表
     * @param $list
     * @return array
     */
    protected function orderFormatLists($list)
    {
        $shop_ids = ArrayHelper::getColumn($list,'shop_id');
        $shop = Shop::find()->select(['id','name','currency'])->where(['id'=>$shop_ids])->indexBy('id')->asArray()->all();
        $order_ids = [];
        foreach ($list as $v) {
            $order_ids[] = $v['order_id'];
        }
        $order_goods_lists = OrderGoods::find()->where(['order_id' => $order_ids, 'goods_status' => [OrderGoods::GOODS_STATUS_UNCONFIRMED, OrderGoods::GOODS_STATUS_NORMAL]])->asArray()->all();
        $order_goods_lists = ArrayHelper::index($order_goods_lists,null,'order_id');

        foreach ($list as &$v){
            $v['shop_name'] = empty($shop[$v['shop_id']])?'':$shop[$v['shop_id']]['name'];
            $order_goods = $order_goods_lists[$v['order_id']];
            $v['goods_count'] = empty($order_goods)?1:count($order_goods);

            $v['goods'] = empty($order_goods)?[[]]:$order_goods;
            $v['country'] = CountryService::getName($v['country']);
            $v['currency'] = empty($shop[$v['shop_id']])?'':$shop[$v['shop_id']]['currency'];
        }
        return $list;
    }

    /**
     * @routeName 商品采购订单列表
     * @routeDescription 商品采购订单列表
     * @throws
     */
    public function actionViewPurchase()
    {
        $req = Yii::$app->request;
        $goods_no = $req->get('goods_no');
        $page = Yii::$app->request->get('page', 1);
        $pageSize = Yii::$app->request->get('limit', 20);

        $goods_model = $this->findModel(['goods_no'=>$goods_no]);
        $searchModel = new PurchaseOrderSearch();
        $searchModel->sku_no = $goods_model['sku_no'];
        $where = $searchModel->search([], 10,1);
        $count = PurchaseOrder::getCountByCond($where);
        $list = PurchaseOrder::getListByCond($where, $page, $pageSize, 'add_time desc');
        $pages = new Pagination(['totalCount' => $count, 'pageSize' => $pageSize]);
        $list = $this->purchaseFormatLists($list);

        $admin_lists = AdminUser::find()->where(['id' => AccessService::getPurchaseUserIds()])->andWhere(['=','status',AdminUser::STATUS_ACTIVE])->select(['id','nickname'])->asArray()->all();
        $admin_lists = ArrayHelper::map($admin_lists,'id','nickname');

        $sub_status_map = [];
        return $this->render($this->render_view . 'view_purchase', [
            'goods' => $goods_model,
            'searchModel' => $searchModel,
            'list' => $list,
            'pages' => $pages,
            'admin_arr' => $admin_lists,
            'sub_status_map' => $sub_status_map
        ]);
    }

    /**
     * 格式化列表
     * @param $list
     * @return array
     */
    protected function purchaseFormatLists($list)
    {
        foreach ($list as &$v) {
            $order_goods = PurchaseOrderService::getOrderGoods($v['order_id']);
            $v['goods_count'] = empty($order_goods) ? 1 : count($order_goods);
            $v['goods'] = empty($order_goods) ? [[]] : $order_goods;
            $goods_num = 0;
            $goods_finish_num = 0;
            foreach ($order_goods as $goods_v) {
                $goods_num += $goods_v['goods_num'];
                $goods_finish_num += $goods_v['goods_finish_num'];
            }
            $v['goods_num'] = $goods_num;
            $v['goods_finish_num'] = $goods_finish_num;
            $channels = PurchaseOrderService::getLogisticsChannels();
            $v['logistics_channels_desc'] = empty($channels[$v['logistics_channels_id']])?'':$channels[$v['logistics_channels_id']];
            $user = User::getInfo($v['admin_id']);
            $v['admin_name'] = empty($user['nickname']) ? '' : $user['nickname'];
        }
        return $list;
    }

    /**
     *
     * @param $data
     * @return mixed
     */
    private function dataPriceDeal($data){
        /*if(!empty($data['status']) && $data['status'] == Goods::GOODS_STATUS_VALID){
            $data['source_method_sub'] = 0;
        }*/
        if(empty($data['electric'])){
            $data['electric'] = Base::ELECTRIC_ORDINARY;
        }
        $data['size'] = GoodsService::genSize($data);
        $source = [];
        if (!empty($data['source'])) {
            foreach ($data['source']['id'] as $k => $v) {
                $platform_url = empty($data['source']['platform_url'][$k]) ? '' : $data['source']['platform_url'][$k];
                $id = empty($data['source']['id'][$k]) ? '' : $data['source']['id'][$k];
                $platform_type = empty($data['source']['platform_type'][$k]) ? '' : $data['source']['platform_type'][$k];
                if (!in_array($platform_type,[Base::PLATFORM_DISTRIBUTOR,Base::PLATFORM_SUPPLIER])) {
                    if (empty($id) && empty($platform_url)) {
                        continue;
                    }
                }
                $supplier_id = empty($data['source']['supplier_id'][$k]) ? 0 : $data['source']['supplier_id'][$k];
                $warehouse_supplier_id = empty($data['source']['warehouse_supplier_id'][$k]) ? 0 : $data['source']['warehouse_supplier_id'][$k];
                $supplier = 0;
                if ($platform_type == Base::PLATFORM_SUPPLIER) {
                    $supplier = $supplier_id;
                }
                if ($platform_type == Base::PLATFORM_DISTRIBUTOR) {
                    $supplier = $warehouse_supplier_id;
                }
                $source[] = [
                    'id' => $id,
                    'platform_url' => $platform_url,
                    'platform_type' => $platform_type,
                    'price' => empty($data['source']['price'][$k]) ? 0 : $data['source']['price'][$k],
                    'supplier_id' => $supplier
                ];
            }
        }
        $data['source'] = $source;

        $property = [];
        if (!empty($data['property'])) {
            foreach ($data['property']['id'] as $k => $v) {
                $id = empty($data['property']['id'][$k]) ? '' : $data['property']['id'][$k];

                $price = empty($data['property']['price'][$k]) ? 0 : $data['property']['price'][$k];
                $gbp_price = empty($data['property']['gbp_price'][$k]) ? 0 : $data['property']['gbp_price'][$k];
                $weight = empty($data['property']['weight'][$k]) ? 0 : $data['property']['weight'][$k];
                $size_l = empty($data['property']['size_l'][$k]) ? '' : $data['property']['size_l'][$k];
                $size_w = empty($data['property']['size_w'][$k]) ? '' : $data['property']['size_w'][$k];
                $size_h = empty($data['property']['size_h'][$k]) ? '' : $data['property']['size_h'][$k];

                $package_size = '';
                if (!empty($size_l) && !empty($size_w) && !empty($size_h)) {
                    $package_size = $size_l . 'x' . $size_w . 'x' . $size_h;
                }

                $property[] = [
                    'id' => $id,
                    'price' => $price,
                    'gbp_price' => $gbp_price,
                    'weight' => $weight,
                    'package_size' => $package_size,
                ];
            }
        }
        unset($data['property']);
        $data['goods_property'] = $property;
        return $data;
    }

    /**
     *
     * @param $data
     * @return mixed
     */
    private function dataDeal($data)
    {
        if (empty($data['electric'])) {
            $data['electric'] = Base::ELECTRIC_ORDINARY;
        }
        $category_id = $data['category_id'];
        $category_arr = explode(',', $category_id);
        $data['category_id'] = (int)end($category_arr);

        $data['size'] = GoodsService::genSize($data);
        $source = [];
        if (!empty($data['source'])) {
            foreach ($data['source']['id'] as $k => $v) {
                $platform_url = empty($data['source']['platform_url'][$k]) ? '' : $data['source']['platform_url'][$k];
                $id = empty($data['source']['id'][$k]) ? '' : $data['source']['id'][$k];
                $platform_type = empty($data['source']['platform_type'][$k]) ? '' : $data['source']['platform_type'][$k];
                if (!in_array($platform_type,[Base::PLATFORM_DISTRIBUTOR,Base::PLATFORM_SUPPLIER])) {
                    if (empty($id) && empty($platform_url)) {
                        continue;
                    }
                }
                $supplier_id = empty($data['source']['supplier_id'][$k]) ? 0 : $data['source']['supplier_id'][$k];
                $warehouse_supplier_id = empty($data['source']['warehouse_supplier_id'][$k]) ? 0 : $data['source']['warehouse_supplier_id'][$k];
                $supplier = 0;
                if ($platform_type == Base::PLATFORM_SUPPLIER) {
                    $supplier = $supplier_id;
                }
                if ($platform_type == Base::PLATFORM_DISTRIBUTOR) {
                    $supplier = $warehouse_supplier_id;
                }
                $source[] = [
                    'id' => $id,
                    'platform_url' => $platform_url,
                    'platform_type' => $platform_type,
                    'price' => empty($data['source']['price'][$k]) ? 0 : $data['source']['price'][$k],
                    'supplier_id' => $supplier
                ];
            }
        }
        $data['source'] = $source;

        $attribute = [];
        if (!empty($data['attribute'])) {
            foreach ($data['attribute']['id'] as $k => $v) {
                $attribute_name = empty($data['attribute']['attribute_name'][$k]) ? '' : $data['attribute']['attribute_name'][$k];
                $id = empty($data['attribute']['id'][$k]) ? '' : $data['attribute']['id'][$k];
                if (empty($id) && empty($attribute_name)) {
                    continue;
                }

                $attribute_value = empty($data['attribute']['attribute_value'][$k]) ? '' : $data['attribute']['attribute_value'][$k];
                $attribute[] = [
                    'id' => $id,
                    'attribute_name' => $attribute_name,
                    'attribute_value' => $attribute_value,
                ];
            }
        }
        $data['attribute'] = $attribute;

        $property = [];
        if (!empty($data['property'])) {
            foreach ($data['property']['id'] as $k => $v) {
                $goods_img = empty($data['property']['goods_img'][$k]) ? '' : $data['property']['goods_img'][$k];
                $id = empty($data['property']['id'][$k]) ? '' : $data['property']['id'][$k];
                $colour = empty($data['property']['colour'][$k]) ? '' : $data['property']['colour'][$k];
                $size = empty($data['property']['size'][$k]) ? '' : $data['property']['size'][$k];

                $price = empty($data['property']['price'][$k]) ? 0 : $data['property']['price'][$k];
                $gbp_price = empty($data['property']['gbp_price'][$k]) ? 0 : $data['property']['gbp_price'][$k];
                $weight = empty($data['property']['weight'][$k]) ? 0 : $data['property']['weight'][$k];
                $size_l = empty($data['property']['size_l'][$k]) ? '' : $data['property']['size_l'][$k];
                $size_w = empty($data['property']['size_w'][$k]) ? '' : $data['property']['size_w'][$k];
                $size_h = empty($data['property']['size_h'][$k]) ? '' : $data['property']['size_h'][$k];

                $package_size = '';
                if (!empty($size_l) && !empty($size_w) && !empty($size_h)) {
                    $package_size = $size_l . 'x' . $size_w . 'x' . $size_h;
                }

                $property[] = [
                    'id' => $id,
                    'goods_img' => $goods_img,
                    'size' => $size,
                    'colour' => $colour,
                    'price' => $price,
                    'gbp_price' => $gbp_price,
                    'weight' => $weight,
                    'package_size' => $package_size,
                ];
            }
        }
        unset($data['property']);
        $data['property'] = empty($data['property_name'])?[]:$data['property_name'];
        $data['goods_property'] = $property;
        return $data;
    }

    /**
     * @routeName 删除商品
     * @routeDescription 删除指定商品
     * @return array
     * @throws
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $goods_model = $this->findModel($id);
        if(!in_array($goods_model['status'],[Goods::GOODS_STATUS_UNALLOCATED,Goods::GOODS_STATUS_WAIT_ADDED,Goods::GOODS_STATUS_UNCONFIRMED])){
            $exist = GoodsShop::find()->where(['goods_no'=>$goods_model['goods_no']])->exists();
            if($exist) {
                return $this->FormatArray(self::REQUEST_FAIL, "该商品数据已被认领无法删除", []);
            }
        }
        if (Goods::deleteAll(['goods_no'=>$goods_model['goods_no']])) {
            GoodsSource::deleteAll(['goods_no'=>$goods_model['goods_no']]);
            GoodsAttribute::deleteAll(['goods_no'=>$goods_model['goods_no']]);
            GoodsChild::deleteAll(['goods_no'=>$goods_model['goods_no']]);
            GoodsAdditional::deleteAll(['goods_no'=>$goods_model['goods_no']]);
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "删除失败", []);
        }
    }

    /**
     * @routeName 复制商品
     * @routeDescription 复制指定商品
     * @return array
     * @throws
     */
    public function actionCopy()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $goods_no = $req->get('goods_no');
        if ((new GoodsService())->copy($goods_no)) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "复制成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "复制失败", []);
        }
    }

    public function actionSupplementaryClaim()
    {
        $req = Yii::$app->request;
        $platform = [];
        $goods_platform = GoodsService::$own_platform_type;
        $where = [];
        if(!AccessService::hasAllShop()) {
            $shop_id = Yii::$app->user->identity->shop_id;
            $shop_id = explode(',', $shop_id);
            $where['id'] = $shop_id;
        }
        $shop = Shop::find()->where($where)->select(['id','platform_type', 'name','country_site'])->asArray()->all();
        foreach ($goods_platform as $k => $v) {
            foreach ($shop as $shop_v){
                if($shop_v['platform_type'] != $k) {
                    continue;
                }
                if(!empty($shop_v['country_site'])){
                    $country_site = explode(',',$shop_v['country_site']);
                    foreach ($country_site as $size){
                        $platform[$shop_v['id'].'_'.$size] = $shop_v['name'].CountryService::getName($size,false);
                    }
                } else {
                    $platform[$shop_v['id']] = $shop_v['name'];
                }
            }
        }
        if ($req->isPost){
        Yii::$app->response->format = Response::FORMAT_JSON;
        $post = $req->post();
        $shop_id = $post['shop_id'];
        $cgoods_no = $post['cgoods_no'];
        $sku_no = $post['sku_no'];
        if (empty($shop_id)){
            return $this->FormatArray(self::REQUEST_FAIL, "店铺不能为空", []);
        }
        if ((new GoodsService())->supplementaryClaim($cgoods_no,$shop_id,$sku_no)) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "补认领成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "补认领失败", []);
        }
        }
        return $this->render($this->render_view.'supplementary_claim',['platform'=>$platform]);
    }


    /**
     * @routeName 批量暂停销售
     * @routeDescription 批量暂停销售
     * @return array
     * @throws
     */
    public function actionBatchCloseView(){
        $req = Yii::$app->request;
        $id = $req->get('id');
        $batch = $req->get('operate');
        $per_info = [];
        $per_info['data'] = '';
        $per_info['goods_no'] = '';
        $per_info['reason'] = '';
        $per_info['category_id'] = '';
        $per_info['id'] = $id;
        if ($req->isPost){
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $goods_id = explode(',',$post['id']);
            $goods = Goods::find()->where(['id'=>$goods_id])->all();
            foreach ($goods as $v){
                if ($v['stock'] == 0){
                    continue;
                }
                $model = new GoodsReason();
                $model['goods_no'] = $v['goods_no'];
                $model['category'] = $v['category_id'];
                $model['reason'] = '0';
                $model['remarks'] = $post['remarks'];
                if($model->save()){
                    $v['stock'] = 0;
                    $goods_change_data = SystemOperlogService::getModelChangeData($v);
                    $v->save();
                    (new GoodsService())->asyncPlatformStock($v['goods_no']);
                    //修改商品日志
                    (new SystemOperlogService())->setType(SystemOperlog::TYPE_UPDATE)
                        ->addGoodsLog($v['goods_no'], $goods_change_data, SystemOperlogService::ACTION_GOODS_CLOSR_STOCK, $model['remarks']);
                }else {
                    return $this->FormatArray(self::REQUEST_FAIL, "停止销售失败", []);
                }
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "停止销售成功", []);
        }
        return $this->render($this->render_view.'close',['per_info'=>$per_info,'batch'=>$batch]);
    }

    /**
     * @routeName 批量复制到精品
     * @routeDescription 批量复制到精品
     * @return array |Response|string
     */
    public function actionCopyFine()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->post('id');
        $goods = Goods::find()->where(['id' => $id])->asArray()->all();
        foreach ($goods as $v) {
            (new GoodsService())->copyFine($v);
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "复制成功", []);
    }

    /**
     * @routeName 认领商品
     * @routeDescription 认领指定商品
     * @return array
     * @throws
     */
    public function actionClaim()
    {
        $req = Yii::$app->request;
        $goods_no = $req->get('goods_no');
        $platform_type = $req->get('platform_type');
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            if(empty($post['shop'])) {
                return $this->FormatArray(self::REQUEST_FAIL, "认领店铺不能为空", []);
            }
            $follow_claim = empty($post['follow_claim'])?0:1;

            try {
                $goods = Goods::findOne(['goods_no'=>$goods_no]);

                if($goods['status'] == Goods::GOODS_STATUS_INVALID) {
                    return $this->FormatArray(self::REQUEST_FAIL, "商品被禁用无法认领", []);
                }

                //映射类目验证
                $category_id = $goods['category_id'];

                $shop_id = [];
                foreach ($post['shop'] as $v){
                    $v = explode('_',$v);
                    if(empty($v[0])){
                        continue;
                    }
                    $shop_id[$v[0]] = $v[0];
                }
                $shop_lists = Shop::find()->where(['id' => $shop_id])->asArray()->all();
                $platform_types = ArrayHelper::getColumn($shop_lists,'platform_type');
                $platform_types = array_unique($platform_types);
                $category_names = Category::find()->where(['id'=>$category_id])->asArray()->all();
                $category_names = ArrayHelper::map($category_names,'id','name');
                $empty_ca = '';
                foreach ($platform_types as $pl_v) {
                    if(empty($category_id)) {
                        if ($goods['source_platform_type'] == Base::PLATFORM_HEPSIGLOBAL && $pl_v == $goods['source_platform_type']) {
                            if (!empty($goods['source_platform_category_id'])) {
                                continue;
                            }
                        }
                        return $this->FormatArray(self::REQUEST_FAIL, "认领平台类目不能为空", []);
                    }
                    if ($pl_v == Base::PLATFORM_WOOCOMMERCE) {
                        $ca_lists = IndependenceCategory::find()->where(['category_id' => $category_id,'platform_type'=>$pl_v])->asArray()->one();
                        if(empty($ca_lists) || empty($ca_lists['mapping'])) {
                            $empty_ca .= ' 未映射类目：' . $category_names[$category_id] . '「' . Base::$platform_maps[$pl_v] . '」' . "<br/>";
                        }
                        continue;
                    }
                    $ca_lists = CategoryMapping::find()->where(['category_id' => $category_id,'platform_type'=>$pl_v])->asArray()->all();
                    $ca_lists = ArrayHelper::index($ca_lists,'category_id');
                    if(empty($ca_lists[$category_id]) || empty($ca_lists[$category_id]['o_category_name'])){
                        $empty_ca .= ' 未映射类目：'. $category_names[$category_id]. '「'.Base::$platform_maps[$pl_v] .'」'."<br/>";
                    }
                }
                if(!empty($empty_ca)){
                    return $this->FormatArray(self::REQUEST_FAIL, "认领失败 "."<br/>" .$empty_ca, []);
                }
                $params = [
                    'follow_claim' => $follow_claim,
                ];
                $result = (new GoodsService())->claim($goods_no, $post['shop'], $goods['source_method'],$params);
                if ($result) {
                    return $this->FormatArray(self::REQUEST_SUCCESS, "认领成功", []);
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, "认领失败", []);
                }
            } catch (\Exception $e) {
                return $this->FormatArray(self::REQUEST_FAIL, "认领失败:" . $e->getMessage().$e->getTraceAsString(), []);
            }

        } else {
            $goods = Goods::findOne(['goods_no'=>$goods_no]);
            $goods_shop = GoodsShop::find()->where(['goods_no'=>$goods_no])->select(['shop_id','country_code'])->asArray()->all();
            $shop_ids = [];
            foreach ($goods_shop as $v) {
                if (empty($v['country_code'])) {
                    $shop_ids[] = $v['shop_id'];
                } else {
                    $shop_ids[] = $v['shop_id'] . '_' . $v['country_code'];
                }
            }
            $platform = [];
            $goods_platform = GoodsService::$own_platform_type;
            if($goods['source_method'] == GoodsService::SOURCE_METHOD_AMAZON){
                $goods_platform = GoodsService::$amazon_platform_type;
            }


            $where = [];
            if(!AccessService::hasAllShop()) {
                $shop_id = Yii::$app->user->identity->shop_id;
                $shop_id = explode(',', $shop_id);
                $where['id'] = $shop_id;
            }
            $where['status'] = [Shop::STATUS_VALID,Shop::STATUS_PAUSE];
            if (!empty($platform_type)) {
                $where['platform_type'] = $platform_type;
            }
            $shop = Shop::find()->where($where)->select(['id','platform_type', 'name','country_site'])->asArray()->all();

            foreach ($goods_platform as $k => $v) {
                //$shop = ArrayHelper::map($shop, 'id', 'name');
                $shop_lists = [];
                foreach ($shop as $shop_v){
                    if($shop_v['platform_type'] != $k) {
                        continue;
                    }
                    if(!empty($shop_v['country_site'])){
                        $country_site = explode(',',$shop_v['country_site']);
                        foreach ($country_site as $size){
                            $shop_lists[$shop_v['id'].'_'.$size] = $shop_v['name'].CountryService::getName($size,false);
                        }
                    } else {
                        $shop_lists[$shop_v['id']] = $shop_v['name'];
                    }
                }

                if(empty($shop_lists)){
                    continue;
                }

                $platform[$k] = [
                    'name' => $v,
                    'shop' => $shop_lists
                ];
            }
            return $this->render('claim', ['goods_no' => $goods_no, 'platform' => $platform,'shop_ids'=>$shop_ids]);
        }

    }

    /**
     * @routeName 批量删除商品
     * @routeDescription 批量删除商品
     * @return array |Response|string
     */
    public function actionBatchDel()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->post('id');
        $goods = Goods::find()->where(['id' => $id])->asArray()->all();
        foreach ($goods as $v) {
            if (!in_array($v['status'], [Goods::GOODS_STATUS_UNALLOCATED, Goods::GOODS_STATUS_WAIT_ADDED, Goods::GOODS_STATUS_UNCONFIRMED])) {
                $exist = GoodsShop::find()->where(['goods_no'=>$v['goods_no']])->exists();
                if($exist) {
                    continue;
                }
            }

            if(Goods::deleteAll(['goods_no' => $v['goods_no']])) {
                GoodsSource::deleteAll(['goods_no' => $v['goods_no']]);
                GoodsAttribute::deleteAll(['goods_no' => $v['goods_no']]);
                GoodsChild::deleteAll(['goods_no' => $v['goods_no']]);
                GoodsAdditional::deleteAll(['goods_no' => $v['goods_no']]);
            }
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        //return $this->FormatArray(self::REQUEST_FAIL, "更新失败", []);
    }

    /**
     * @routeName 批量类目错误
     * @routeDescription 批量类目错误
     * @return array |Response|string
     */
    public function actionBatchErrorCategory()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->post('id');
        $goods = Goods::find()->where(['id' => $id])->all();
        foreach ($goods as $v) {
            if (!in_array($v['status'], [Goods::GOODS_STATUS_WAIT_MATCH])) {
                continue;
            }
            $v['goods_stamp_tag'] = Goods::GOODS_TORT_TYPE_CATEGORY_ERROR;
            $v->save();
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "设置成功", []);
    }

    /**
     * @routeName 批量更新状态
     * @routeDescription 批量更新状态
     * @return array |Response|string
     */
    public function actionBatchUpdateStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        if ($req->isPost) {
            $id = $req->post('id');
            $status = $req->post('status');
        }else{
            $id = $req->get('id');
            $status = $req->get('status');
        } 
        if(strstr($id,',')){
        	$id = explode(",", $id);
        }
        
        $goods = Goods::find()->where(['id' => $id])->all();
        //if($status == Goods::GOODS_STATUS_VALID){
            foreach ($goods as $v) {
            	$model = new GoodsReason();
            	$reason='';
                if($status == Goods::GOODS_STATUS_VALID) {
                    $v['status'] = Goods::GOODS_STATUS_VALID;
                    if ($v['weight'] <= 0) {
                        $v['status'] = Goods::GOODS_STATUS_WAIT_MATCH;
                    }
                } else {
                    $v['status'] = Goods::GOODS_STATUS_INVALID;
                }
                $goods_change_data = SystemOperlogService::getModelChangeData($v);
                $v->save();
                if($status == Goods::GOODS_STATUS_INVALID) {
                $model['reason'] = $req->post('reason');
                $model['remarks'] = $req->post('remarks');
                $model['goods_no'] = $v['goods_no'];
                $model['category'] = $v['category_id'];
                $model->save();
                }
                if(!empty($model['reason'])){
                	$reason = Goods::$reason_map[$model['reason']].$model['remarks'];
                }
                (new GoodsService())->asyncPlatformStock($v['goods_no']);
                //修改商品日志
                (new SystemOperlogService())->setType(SystemOperlog::TYPE_UPDATE)
                    ->addGoodsLog($v['goods_no'], $goods_change_data, SystemOperlogService::ACTION_GOODS_UPDATE_STATUS, $reason);
            }
            $result = true;
        /*}else{
            $result = Goods::updateAll(['status'=>$status],['id'=>$id]);
        }*/

            if ($result) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "状态更新成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "状态更新失败", []);
        }
    }
    /**
     * 禁用弹出表格表格
     */
    public function actionDisable(){
    	$req=Yii::$app->request;
    	$id=$req->get("id");
    	$per_info['id']=$id;
    	$per_info['status']=$req->get("status");
    	$per_info['reason']="";
    	$per_info['data']="";
    	return $this->render('goods/disable',['per_info'=>$per_info]);
    }
    /**
     * 開啓銷售
     * 
     */
    public function actionOpenStock()
    {
    	Yii::$app->response->format = Response::FORMAT_JSON;
    	$req = Yii::$app->request;
    	$id = $req->get('id');
    	$goods = Goods::findOne($id);
    	$goods['stock'] =1;
    	$goods_change_data = SystemOperlogService::getModelChangeData($goods);
    	$goods->save();
    	$ac=new GoodsReason();
    	$d=$ac->find()->where(['goods_no' => $goods['goods_no']])->all();
    	foreach ($d as $v) {
    	$v->delete();}
        (new GoodsService())->asyncPlatformStock($goods['goods_no']);
        //修改商品日志
    	(new SystemOperlogService())->setType(SystemOperlog::TYPE_UPDATE)
    	->addGoodsLog($goods['goods_no'], $goods_change_data, SystemOperlogService::ACTION_GOODS_OPEN_STOCK, '');
    	return $this->FormatArray(self::REQUEST_SUCCESS, "开始销售", []);
    }
    /**
     * 停止銷售
     *
     */
    public function actionCloseStock()
    {
    	Yii::$app->response->format = Response::FORMAT_JSON;
    	$req = Yii::$app->request;
    	$model = new GoodsReason();
    	$id = $req->post('id');   
    	$model['goods_no'] = $req->post('goods');
    	$model['category'] = $req->post('category');
    	$model['reason'] = $req->post('reason');
    	$model['remarks'] = $req->post('remarks');
    	if($model->save()){
    		$goods = Goods::findOne($id);
    		$goods['stock'] =0;
    		$goods_change_data = SystemOperlogService::getModelChangeData($goods);
    		$goods->save();
            (new GoodsService())->asyncPlatformStock($goods['goods_no']);
    		//修改商品日志
    		(new SystemOperlogService())->setType(SystemOperlog::TYPE_UPDATE)
    		->addGoodsLog($goods['goods_no'], $goods_change_data, SystemOperlogService::ACTION_GOODS_CLOSR_STOCK, $model['remarks']);
    		return $this->FormatArray(self::REQUEST_SUCCESS, "停止销售", []);
    	}else {
    		return $this->FormatArray(self::REQUEST_FAIL, "提交失败", []);
    	}
    	
    }
    /**
     * 跳转至停止销售输入原因界面
     */
    public function actionCloseView()
    {
    	$req=Yii::$app->request;
    	$id=$req->get("id");	
    	$perModel =Goods::findOne($id);
    	$per_info= $perModel->toArray();
    	$per_info['reason']="";
    	$per_info['data']="";
    	return $this->render('goods/close',['per_info'=>$per_info]);
    }

    /**
     * @routeName 批量移入海外仓
     * @routeDescription 批量移入海外仓
     * @return array |Response|string
     */
    public function actionBatchAddOverseas()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->post('id');
        $goods = Goods::find()->where(['id' => $id])->asArray()->all();
        foreach ($goods as $v) {
            if ($v['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                return $this->FormatArray(self::REQUEST_FAIL, "移入失败，存在多变体商品无法移入海外仓", []);
            }
        }

        try {
            $goods_nos = ArrayHelper::getColumn($goods, 'goods_no');
            (new OverseasGoodsService())->addOverseas($goods_nos);
        } catch (\Exception $e) {
            CommonUtil::logs('移入失败 ' . $e->getMessage(), 'batch_add_overseas');
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "移入成功", []);
    }
    /**
     * @routeName 批量移入精品
     * @routeDescription 批量移入精品
     * @return array |Response|string
     */
    public function actionBatchAddFind()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $platform_types = [Base::PLATFORM_OZON => 'Ozon',Base::PLATFORM_ALLEGRO => 'Allegro', 'find' => '精选商品库'];
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $id = $req->post('id');
            $platform_type = $req->post('platform_type');
            $goods = Goods::find()->where(['id' => explode(',',$id)])->asArray()->all();
            $goods = ArrayHelper::getColumn($goods,'goods_no');
            $lists = [];
            foreach ($platform_type as $v) {
                $lists[$v] = $goods;
            }
            try {
                (new FindGoodsService())->addOverseas($lists);
            } catch (\Exception $e) {
                CommonUtil::logs('移入失败 ' . $e->getMessage(), 'batch_add_overseas');
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "移入成功", []);
        }
        return $this->render('goods/batch-add-find',['id' => $id,'platform_types' => $platform_types]);
    }
    /**
     * @routeName 海外批量移入精品
     * @routeDescription 海外批量移入精品
     * @return array |Response|string
     */
    public function actionBatchAddFinds()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->post('id');
        $goods = Goods::find()->where(['id' => $id])->asArray()->all();
        try {
            $goods_nos = ArrayHelper::getColumn($goods, 'goods_no');
            (new FindGoodsService())->addOverseass($goods_nos);
        } catch (\Exception $e) {
            CommonUtil::logs('移入失败 ' . $e->getMessage(), 'batch_add_overseas');
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "移入成功", []);
    }

    /**
     * @routeName 批量分配商品
     * @routeDescription 批量分配商品
     * @return array
     * @throws
     */
    public function actionBatchAllo()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $type = $req->get('type',1);//1为编辑 2为归属
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            if(empty($post['admin_id'])) {
                return $this->FormatArray(self::REQUEST_FAIL, "平台商品数据处理员不能为空", []);
            }

            if(!empty($id)) {
                $goods = Goods::find()->where(['id' => explode(',', $id)])->all();
            }else{
                if(empty($post['limit']) || $post['limit'] <= 0) {
                    return $this->FormatArray(self::REQUEST_FAIL, "认领数量错误", []);
                }
                $source_method = $req->get('source_method');
                $tag = $req->get('tag');
                $goods_stamp_tag = $req->get('goods_stamp_tag');
                $goods_tort_type = $req->get('goods_tort_type');
                $searchModel = new GoodsSearch();
                $searchModel->goods_tort_type = $goods_tort_type;
                $searchModel->goods_stamp_tag = $goods_stamp_tag;
                $where = $searchModel->search(Yii::$app->request->queryParams, $tag,$source_method);
                $goods = Goods::dealWhere($where)->limit($post['limit'])->all();
                if(count($goods) > 10000) {
                    return $this->FormatArray(self::REQUEST_FAIL, "认领数量不超过10000", []);
                }
            }
            if(empty($goods)) {
                return $this->FormatArray(self::REQUEST_FAIL, "分配商品不能为空", []);
            }

            foreach ($goods as $v) {
                if($type == 1) {
                    if (!in_array($v['status'], [Goods::GOODS_STATUS_UNCONFIRMED, Goods::GOODS_STATUS_UNALLOCATED, Goods::GOODS_STATUS_WAIT_ADDED])) {
                        continue;
                    }
                }
                try {
                    if($type == 1) {
                        $v['admin_id'] = (int)$post['admin_id'];
                        $v['status'] = Goods::GOODS_STATUS_WAIT_ADDED;
                    }else{
                        $v['owner_id'] = (int)$post['admin_id'];
                    }
                    $v->save();
                } catch (\Exception $e) {
                    CommonUtil::logs($v['goods_no'].' 分配失败 '.$e->getMessage(),'batch_allo');
                }
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "分配成功", []);
        } else {
            $source_method = $req->get('source_method');
            if($type == 1){
                $admin_ids = AccessService::getGoodsSupplementUserIds();
            }else{
                $admin_ids = AccessService::getShopOperationUserIds();
            }
            $admin_lists = AdminUser::find()->where(['id' => $admin_ids])->andWhere(['=','status',AdminUser::STATUS_ACTIVE])->select(['id','nickname'])->asArray()->all();
            $admin_lists = ArrayHelper::map($admin_lists,'id','nickname');
            return $this->render('goods/allo', ['admin_lists' => $admin_lists,'type' => $type,'id'=>$id]);
            //return $this->render('goods/allo', ['admin_lists' => $admin_lists,'type' => $type]);
        }
    }

    /**
     * @routeName 批量设置类目
     * @routeDescription 批量设置类目
     * @return array
     * @throws
     */
    public function actionBatchUpdateCategory()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');

        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();

            $category_id = $post['category_id'];
            $category_arr = explode(',',$category_id);
            $category_id = (int)end($category_arr);
            if(empty($category_id)) {
                return $this->FormatArray(self::REQUEST_FAIL, "类目不能为空", []);
            }

            if(!empty($id)) {
                $goods = Goods::find()->where(['id' => explode(',', $id)])->all();
            }else{
                /*$source_method = $req->get('source_method');
                $searchModel = new GoodsSearch();
                $where = $searchModel->search(Yii::$app->request->queryParams, 2,$source_method);
                $goods = Goods::getAllByCond($where,['id' => SORT_DESC],'category_id,goods_no,source_method,status');*/
            }
            if(empty($goods)) {
                return $this->FormatArray(self::REQUEST_FAIL, "商品不能为空", []);
            }

            foreach ($goods as $v) {
                try {
                    $v['category_id'] = $category_id;
                    $goods_change_data = SystemOperlogService::getModelChangeData($v);
                    $v->save();
                    (new GoodsService())->updateCategory($v['goods_no'],$category_id);
                    //修改商品日志
                    (new SystemOperlogService())->setType(SystemOperlog::TYPE_UPDATE)
                        ->addGoodsLog($v['goods_no'], $goods_change_data, SystemOperlogService::ACTION_GOODS_UPDATE_CATEGORY, '');
                } catch (\Exception $e) {
                    CommonUtil::logs($v['goods_no'].' 设置类目失败 '.$e->getMessage(),'batch_update_category');
                }
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "设置类目成功", []);
        } else {
            $source_method = $req->get('source_method');
            return $this->render('goods/batch-update-category', ['source_method' => $source_method]);
        }
    }

    /**
     * @routeName 批量设置颜色
     * @routeDescription 批量设置颜色
     * @return array
     * @throws
     */
    public function actionBatchUpdateColour()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');

        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();

            $colour = $post['colour'];
            if(empty($colour)) {
                return $this->FormatArray(self::REQUEST_FAIL, "颜色不能为空", []);
            }

            if(!empty($id)) {
                $goods = Goods::find()->where(['id' => explode(',', $id)])->all();
            }else{
                /*$source_method = $req->get('source_method');
                $searchModel = new GoodsSearch();
                $where = $searchModel->search(Yii::$app->request->queryParams, 2,$source_method);
                $goods = Goods::getAllByCond($where,['id' => SORT_DESC],'category_id,goods_no,source_method,status');*/
            }
            if(empty($goods)) {
                return $this->FormatArray(self::REQUEST_FAIL, "商品不能为空", []);
            }

            foreach ($goods as $v) {
                try {
                    $v['colour'] = $colour;
                    $v->save();
                } catch (\Exception $e) {
                    CommonUtil::logs($v['goods_no'].' 设置颜色失败 '.$e->getMessage(),'batch_update_colour');
                }
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "设置颜色成功", []);
        } else {
            return $this->render('goods/batch-update-colour');
        }
    }

    /**
     * @routeName 批量归类
     * @routeDescription 批量归类
     * @return array
     * @throws
     */
    public function actionBatchUpdateTortType()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();

            if(!isset($post['tort_type'])){
                return $this->FormatArray(self::REQUEST_FAIL, "归类不能为空", []);
            }

            $tort_type = $post['tort_type'];
            if(!empty($id)) {
                $goods = Goods::find()->where(['id' => explode(',', $id)])->all();
            }else{
                /*$source_method = $req->get('source_method');
                $searchModel = new GoodsSearch();
                $where = $searchModel->search(Yii::$app->request->queryParams, 2,$source_method);
                $goods = Goods::getAllByCond($where,['id' => SORT_DESC],'category_id,goods_no,source_method,status');*/
            }
            if(empty($goods)) {
                return $this->FormatArray(self::REQUEST_FAIL, "商品不能为空", []);
            }

            foreach ($goods as $v) {
                try {
                    $v['goods_tort_type'] = (int)$tort_type;
                    $v['owner_id'] = \Yii::$app->user->identity->id;
                    $v->save();
                } catch (\Exception $e) {
                    CommonUtil::logs($v['goods_no'].' 归类失败 '.$e->getMessage(),'batch_update_tort_type');
                }
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "归类成功", []);
        } else {
            return $this->render('goods/batch-update-tort-type');
        }
    }

    /**
     * @routeName 更新库存
     * @routeDescription 更新库存
     * @return array
     * @throws
     */
    public function actionSetStock()
    {
        $req = Yii::$app->request;
        $cgoods_no = $req->get('cgoods_no');
        $warehouse = $req->get('warehouse_id', WarehouseService::WAREHOUSE_OWN);

        //$goods = Goods::find()->where(['goods_no' => $cgoods_no])->one();
        $goods_child = GoodsChild::find()->where(['cgoods_no' => $cgoods_no])->one();
        if (empty($goods_child)) {
            return $this->FormatArray(self::REQUEST_FAIL, "商品不能为空", []);
        }


        $goods_stock = GoodsStock::find()->where(['cgoods_no'=>$cgoods_no])->select(['warehouse','num'])->all();
        $goods_stock = ArrayHelper::map($goods_stock,'warehouse','num');

        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $stock_nums = $post['stock'];

            foreach (WarehouseService::getSettableWareHouseLists() as $warehouse_v){
                $warehouse_id = $warehouse_v['id'];
                if(!isset($stock_nums[$warehouse_id])){
                    continue;
                }

                $goods_stock[$warehouse_id] = empty($goods_stock[$warehouse_id])?0:$goods_stock[$warehouse_id];
                if($stock_nums[$warehouse_id] + $goods_stock[$warehouse_id] < 0) {
                    return $this->FormatArray(self::REQUEST_FAIL, "库存不能小于0", []);
                }

                $stock = $stock_nums[$warehouse_id];
                if($stock != 0) {
                    GoodsStockService::changeStock($cgoods_no, $warehouse_id, GoodsStockService::TYPE_ADMIN_CHANGE, $stock);
                }

                //库存小于0 把占用库存清空
                if($stock < 0) {
                    OrderStockOccupy::deleteAll(['sku_no' => $goods_child['sku_no'], 'type' => OrderStockOccupy::TYPE_STOCK, 'warehouse'=>$warehouse_id]);
                }

                if($stock > 0) {
                    $order_stock = OrderStockOccupy::find()->where(['sku_no' => $goods_child['sku_no'], 'type' => OrderStockOccupy::TYPE_ON_WAY, 'warehouse'=>$warehouse_id])->all();
                    foreach ($order_stock as $stock_v) {
                        if ($stock - $stock_v['num'] < 0) {
                            continue;
                        }
                        $stock = $stock - $stock_v['num'];
                        $stock_v->delete();
                    }
                }
                (new PurchaseProposalService())->updatePurchaseProposal($warehouse_id,$goods_child['sku_no']);
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "更新库存成功", []);
            //return $this->FormatArray(self::REQUEST_FAIL, "更新库存失败", []);
        } else {
            $stock_lists = [];
            $goods_stock_log = [];
            $occupy_log = [];
            $warehouse_map = WarehouseService::getWarehouseMap();
            foreach (WarehouseService::getSettableWareHouseLists() as $v){
                $warehouse_id = $v['id'];
                if(!isset($goods_stock[$warehouse_id])) {
                    continue;
                }

                $occupy_stock_lists = OrderStockOccupy::find()->where(['sku_no'=>$goods_child['sku_no'],'type'=>OrderStockOccupy::TYPE_STOCK,'warehouse'=>$warehouse_id])->asArray()->all();
                $stock_log = GoodsStockLog::find()->where(['goods_no'=>$cgoods_no,'warehouse'=>$warehouse_id])->orderBy('add_time desc,id desc')->limit(30)->asArray()->all();
                $occupy_order_ids = ArrayHelper::getColumn($occupy_stock_lists,'order_id');
                $order_ids = ArrayHelper::getColumn($stock_log,'type_id');
                $order_ids = array_unique(array_filter($order_ids));
                $order_ids = array_merge($occupy_order_ids,$order_ids);
                $order_lists = Order::find()->where(['order_id'=>$order_ids])->select('order_id,relation_no')->asArray()->all();
                $relation_nos = ArrayHelper::map($order_lists,'order_id','relation_no');
                $puchase_order_lists = PurchaseOrder::find()->where(['order_id'=>$order_ids])->select('order_id,relation_no')->asArray()->all();
                $puchase_relation_nos = ArrayHelper::map($puchase_order_lists,'order_id','relation_no');

                $occupy_stock = 0;
                foreach ($occupy_stock_lists as $occupy_v){
                    $occupy_stock += $occupy_v['num'];
                    $occupy_v['relation_no'] = empty($relation_nos[$occupy_v['order_id']])?'':$relation_nos[$occupy_v['order_id']];
                    $occupy_log[] = $occupy_v;
                }

                $stock_lists[$warehouse_id] = [
                    'warehouse' => $v['warehouse_name'],
                    'field' => $warehouse_id,
                    'stock' => empty($goods_stock[$warehouse_id])?0:$goods_stock[$warehouse_id],
                    'occupy' => empty($occupy_stock)?0:$occupy_stock,
                ];

                $stock_log = array_map(function ($info) use($relation_nos,$puchase_relation_nos){
                    $info['relation_no'] = empty($relation_nos[$info['type_id']])?'':$relation_nos[$info['type_id']];
                    if(empty($info['relation_no'])) {
                        $info['relation_no'] = empty($puchase_relation_nos[$info['type_id']]) ? '' : $puchase_relation_nos[$info['type_id']];
                    }
                    return $info;
                },$stock_log);

                $goods_stock_log[$warehouse_id] =
                    [
                        'warehouse' => $v['warehouse_name'],
                        'log' => $stock_log
                    ];
            }
            return $this->render('goods/set-stock', ['goods' => $goods_child,'stock'=>$stock_lists,'goods_stock_log'=>$goods_stock_log,'occupy_log'=>$occupy_log,'warehouse_id'=>$warehouse,'warehouse_map' => $warehouse_map]);
        }
    }

    /**
     * @routeName 批量认领商品
     * @routeDescription 批量领指定商品
     * @return array
     * @throws
     */
    public function actionBatchClaim()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');

        if ($req->isPost) {
            set_time_limit(0);
            ini_set('memory_limit', '512M');
            ob_end_clean();
            ob_implicit_flush();
            header('X-Accel-Buffering: no');
            echo "开始准备认领<br/>";

            Yii::$app->response->format = Response::FORMAT_RAW;
            $post = $req->post();
            if(empty($post['shop'])) {
                return $this->FormatArray(self::REQUEST_FAIL, "认领店铺不能为空", []);
            }
            $follow_claim = empty($post['follow_claim'])?0:1;

            if(!empty($id)) {
                $goods = Goods::find()->select('category_id,goods_no,source_method,status,source_platform_type,source_platform_category_id')->where(['id' => explode(',', $id)])->asArray()->all();
            }else{
                if(empty($post['limit']) || $post['limit'] <= 0) {
                    return $this->FormatArray(self::REQUEST_FAIL, "认领数量错误", []);
                }
                $source_method = $req->get('source_method');
                $tag = $req->get('tag');
                $goods_stamp_tag = $req->get('goods_stamp_tag');
                $goods_tort_type = $req->get('goods_tort_type');
                $searchModel = new GoodsSearch();
                $searchModel->goods_tort_type = $goods_tort_type;
                $searchModel->goods_stamp_tag = $goods_stamp_tag;
                $where = $searchModel->search(Yii::$app->request->queryParams, $tag,$source_method);
                $limit = $post['limit'];
                echo "执行查询语句此处时间会等待比较长......<br/>";
                $goods = Goods::getListByCond($where,1,$limit,null,'category_id,goods_no,source_method,status,source_platform_type,source_platform_category_id');
                //$goods = Goods::getAllByCond($where,['id' => SORT_DESC],'category_id,goods_no,source_method,status,source_platform_type,source_platform_category_id');
            }
            if(empty($goods)) {
                return $this->FormatArray(self::REQUEST_FAIL, "认领商品不能为空", []);
            }

            //映射类目验证
            $category_ids = ArrayHelper::getColumn($goods,'category_id');
            $category_ids = array_unique($category_ids);
            $shop_id = [];
            foreach ($post['shop'] as $v){
                $v = explode('_',$v);
                if(empty($v[0])){
                    continue;
                }
                $shop_id[$v[0]] = $v[0];
            }
            $shop_lists = Shop::find()->where(['id' => $shop_id])->asArray()->all();
            $shop_lists = ArrayHelper::index($shop_lists,'id');
            $platform_types = ArrayHelper::getColumn($shop_lists,'platform_type');
            $platform_types = array_unique($platform_types);
            $category_names = Category::find()->where(['id'=>$category_ids])->asArray()->all();
            $category_names = ArrayHelper::map($category_names,'id','name');
            $empty_ca = [];
            $ca = [];
            foreach ($platform_types as $pl_v) {
                if(empty($pl_v)){
                    continue ;
                }
                $ca_lists = CategoryMapping::find()->where(['category_id' => $category_ids,'platform_type'=>$pl_v])->asArray()->all();
                $ca_lists = ArrayHelper::index($ca_lists,'category_id');
                foreach ($category_ids as $ca_id){
                    if(empty($ca_id)){
                        continue ;
                    }
                    if ($pl_v == Base::PLATFORM_WOOCOMMERCE) {
                        $in_lists = IndependenceCategory::find()->where(['category_id' => $ca_id,'platform_type'=>$pl_v])->asArray()->one();
                        if(empty($in_lists) || empty($in_lists['mapping'])) {
                            $ca[] = ['category_id' => $ca_id,'platform_type'=>$pl_v];
                        }
                        continue;
                    }
                    if(empty($ca_lists[$ca_id]) || empty($ca_lists[$ca_id]['o_category_name'])){
                        $empty_ca[] = $category_names[$ca_id]. '「'.Base::$platform_maps[$pl_v] .'」'."<br/>";
                        $ca[] = ['category_id' => $ca_id,'platform_type'=>$pl_v];
                    }
                }
            }
            /*if(!empty($empty_ca)){
                return $this->FormatArray(self::REQUEST_FAIL, "认领失败 "."<br/>" .$empty_ca, []);
            }*/
            $success_i = 0;
            $index_i = 0;
            foreach ($goods as $v) {
                $index_i ++;
                echo $index_i.','.$v['goods_no'].',';
                try {
                    if($v['status'] == Goods::GOODS_STATUS_INVALID) {
                        continue;
                    }
                    $shop_ids = [];
                    foreach ($post['shop'] as $shop_v) {
                        $shop_arr = explode('_',$shop_v);
                        $exist_ca = false;
                        foreach ($ca as $ca_v) {
                            if ($ca_v['category_id'] == $v['category_id'] && $ca_v['platform_type'] == $shop_lists[$shop_arr[0]]['platform_type']) {
                                $exist_ca = true;
                                break 1;
                            }
                        }

                        if(empty($v['category_id'])){
                            if($shop_lists[$shop_arr[0]]['platform_type'] != Base::PLATFORM_B2W) {//b2w不需要类目映射
                                if ($shop_lists[$shop_arr[0]]['platform_type'] != $v['source_platform_type'] || empty($v['source_platform_category_id'])) {
                                    $exist_ca = true;
                                }
                            }
                        }

                        if($v['source_platform_type'] == Base::PLATFORM_HEPSIGLOBAL) {
                            if ($shop_lists[$shop_arr[0]]['platform_type'] == $v['source_platform_type'] && !empty($v['source_platform_category_id'])) {
                                $exist_ca = false;
                            }
                        }

                        if ($exist_ca) {
                            continue;
                        }
                        $shop_ids[] = $shop_v;
                    }
                    //$shop_lists[$post['shop']]
                    if(empty($shop_ids)) {
                        echo "---,认领失败,类目未映射或类目不存在<br/>";
                    } else {
                        $params = [
                            'show_log' => true,
                            'follow_claim' => $follow_claim,
                        ];
                        $resullt = (new GoodsService())->claim($v['goods_no'], $shop_ids, $v['source_method'], $params);
                        if ($resullt) {
                            $success_i++;
                        }
                    }
                } catch (\Exception $e) {
                    echo "---,认领失败," . $e->getMessage() . "<br/>";
                    CommonUtil::logs($v['goods_no'].' 认领失败 '.$e->getMessage(),'batch_claim');
                }
            }
            echo '执行商品认领完成！本次成功商品数'.$success_i."<br/>";
            echo '本次未映射类目：'."<br/>";
            foreach ($empty_ca as $v) {
                echo $v;
            }
            exit;
            /*if(!empty($empty_ca)){
                return $this->FormatArray(self::REQUEST_SUCCESS, "认领失败 "."<br/>" .$empty_ca, []);
            }else{
                return $this->FormatArray(self::REQUEST_SUCCESS, "认领成功", []);
            }*/
        } else {
            $source_method = $req->get('source_method');
            $platform = [];
            $goods_platform = GoodsService::$own_platform_type;
            if($source_method == GoodsService::SOURCE_METHOD_AMAZON){
                $goods_platform = GoodsService::$amazon_platform_type;
            }


            $where = [];
            if(!AccessService::hasAllShop()) {
                $shop_id = Yii::$app->user->identity->shop_id;
                $shop_id = explode(',', $shop_id);
                $where['id'] = $shop_id;
            }
            $where['status'] = [Shop::STATUS_VALID,Shop::STATUS_PAUSE];
            $shop = Shop::find()->where($where)->select(['id', 'platform_type','name','country_site'])->asArray()->all();

            foreach ($goods_platform as $k => $v) {
                //$shop = ArrayHelper::map($shop, 'id', 'name');
                $shop_lists = [];
                foreach ($shop as $shop_v){
                    if($shop_v['platform_type'] != $k) {
                        continue;
                    }

                    if(!empty($shop_v['country_site'])){
                        $country_site = explode(',',$shop_v['country_site']);
                        foreach ($country_site as $size){
                            $shop_lists[$shop_v['id'].'_'.$size] = $shop_v['name'].CountryService::getName($size,false);
                        }
                    } else {
                        $shop_lists[$shop_v['id']] = $shop_v['name'];
                    }
                }

                if(empty($shop_lists)){
                    continue;
                }
                $platform[$k] = [
                    'name' => $v,
                    'shop' => $shop_lists
                ];
            }
            return $this->render('batch_claim', ['platform' => $platform,'id'=>$id]);
        }

    }

    /**
     * @routeName 导出未匹配类目
     * @routeDescription 导出未匹配类目
     * @return array
     * @throws
     */
    public function actionExportWaitMatchCategory()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $where = [];
        $where['source_method_sub'] = Goods::GOODS_SOURCE_METHOD_SUB_GRAB;
        $where['source_method'] = GoodsService::SOURCE_METHOD_OWN;
        $list = Goods::find()->where($where)->andWhere(['=','category_id',''])
            ->groupBy('source_platform_type,source_platform_category_id')
            ->select('source_platform_type,source_platform_category_id,source_platform_category_name,count(*) cut')->asArray()->all();

        $data = [];
        foreach ($list as $k => $v) {
            $category_name = $v['source_platform_category_name'];
            $category_name_arr = explode('>',$category_name);
            $data[$k]['source'] = Base::$platform_maps[$v['source_platform_type']];
            $data[$k]['source_platform_category_id'] = $v['source_platform_category_id'];
            $data[$k]['source_platform_category_name'] = $category_name;
            $data[$k]['last_source_platform_category_name'] = end($category_name_arr);
            $data[$k]['cut'] = $v['cut'];
        }

        $column = [
            'source' => '来源平台',
            'source_platform_category_id' => '来源平台类目id',
            'source_platform_category_name' => '来源平台类目',
            'last_source_platform_category_name' => '来源平台子级类目',
            'cut' => '数量',
        ];

        $result = [
            'key' => array_keys($column),
            'header' => array_values($column),
            'list' => $data,
            'fileName' => '未匹配类目导出' . date('ymdhis')
        ];
        return $this->FormatArray(self::REQUEST_SUCCESS, "", $result);
    }

    /**
     * @routeName 导出商品标题
     * @routeDescription 导出商品标题
     * @return array
     * @throws
     */
    public function actionExportTitle()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $tag = $req->get('tag');
        $source_method = $req->get('source_method');
        $goods_stamp_tag = $req->get('goods_stamp_tag');
        $goods_tort_type = $req->get('goods_tort_type');

        $page = $req->get('page',1);
        $config = $req->get('config') ? true : false;
        $page_size = 1000;
        $export_ser = new ExportService($page_size);

        $searchModel = new GoodsSearch();
        $searchModel->goods_stamp_tag = $goods_stamp_tag;
        $searchModel->goods_tort_type = $goods_tort_type;
        $where = $searchModel->search(Yii::$app->request->queryParams, $tag,$source_method);
        if ($config) {
            $count = Goods::getCountByCond($where);
            $result = $export_ser->forHeadConfig($count);
            return $this->FormatArray(self::REQUEST_SUCCESS, "", $result);
        }

        //$list = Goods::getAllByCond($where,['id' => SORT_ASC],'goods_no,sku_no,category_id,source_method,goods_name,goods_name_cn,goods_short_name_cn,goods_keywords');
        $list = Goods::getListByCond($where, $page, $page_size,['id' => SORT_ASC],'goods_no,sku_no,category_id,source_method,goods_name,goods_name_cn,goods_short_name_cn,goods_keywords');

        $data = [];
        foreach ($list as $k => $v) {
            $data[$k]['goods_no'] = $v['goods_no'];
            $data[$k]['sku_no'] = $v['sku_no'];
            $data[$k]['category_name'] = Category::getCategoryName($v['category_id']);
            $data[$k]['goods_name'] = $v['goods_name'];
            $data[$k]['goods_name_cn'] = $v['goods_name_cn'];
            $data[$k]['goods_short_name_cn'] = $v['goods_short_name_cn'];
            $data[$k]['goods_keywords'] = $v['goods_keywords'];
        }

        $column = [
            'goods_no' => '商品编号',
            'sku_no' => '商品sku',
            'category_name' => '平台类目',
            'goods_name' => '商品标题',
            'goods_name_cn' => '中文商品标题',
            'goods_short_name_cn' => '中文商品短标题',
            'goods_keywords' => '商品关键词',
        ];

        $result = $export_ser->forData($column,$data,'商品标题导出' . date('ymdhis'));
        return $this->FormatArray(self::REQUEST_SUCCESS, "", $result);
    }

    /**
     * @routeName 导入关键字
     * @routeDescription 导入关键字
     * @return array
     * @throws
     */
    public function actionImportKeywords()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $file = UploadedFile::getInstanceByName('file');
        if (!in_array($file->extension, ['xlsx', 'xls'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "只允许使用以下文件扩展名的文件：xlsx, xls。", []);
        }

        // 读取excel文件
        $data = Excel::import($file->tempName, [
            'setFirstRecordAsKeys' => false,
        ]);

        // 多Sheet
        if (isset($data[0])) {
            $data = $data[0];
        }

        $rowKeyTitles = [
            'goods_no' => '商品编号',
            'sku_no' => '商品sku',
            'goods_name' => '商品标题',
            'goods_name_cn' => '中文商品标题',
            'goods_short_name_cn' => '中文商品短标题',
            'goods_keywords' => '商品关键词',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }
        /*if(empty($keyMap['goods_no']) || empty($keyMap['goods_keywords']) || empty($keyMap['goods_short_name_cn'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }*/
        if(empty($keyMap['sku_no']) || empty($keyMap['goods_short_name_cn']) || empty($keyMap['goods_keywords'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }

        $count = count($data);
        $success = 0;
        $errors = [];
        for ($i = 2; $i <= $count; $i++) {
            $row = $data[$i];
            foreach ($row as &$rowValue) {
                $rowValue = !empty($rowValue) ? str_replace(' ', ' ', $rowValue) : '';
                $rowValue = !empty($rowValue) ? trim($rowValue) : '';
            }

            foreach (array_keys($rowKeyTitles) as $rowMapKey) {
                $rowKey = isset($keyMap[$rowMapKey]) ? $keyMap[$rowMapKey] : '';
                $$rowMapKey = isset($row[$rowKey]) ? trim($row[$rowKey]) : '';
            }

            /*if ((empty($goods_no) && empty($sku_no)) || empty($goods_name_cn)) {
                $errors[$i] = '商品编号或商品标题或商品关键词不能为空';
                continue;
            }*/
            if ((empty($goods_no) && empty($sku_no)) || empty($goods_keywords) || empty($goods_short_name_cn)) {
                $errors[$i] = '商品编号或商品标题或商品关键词不能为空';
                continue;
            }

            try {
                $where = [];
                if(empty($goods_no) && !empty($sku_no)) {
                    $where['sku_no'] = $sku_no;
                }
                if(!empty($goods_no)) {
                    $where['goods_no'] = $goods_no;
                }
                if(empty($where)){
                    $errors[$i] = '商品编号或商品sku不能为空';
                    continue;
                }
                $goods = Goods::find()->where($where)->one();
                $goods_no = $goods['goods_no'];
                if (empty($goods)){
                    $errors[$i] = '找不到该商品';
                    continue;
                }
                /*if(empty($goods_name_cn)){
                    continue;
                }
                $goods->goods_name_cn = $goods_name_cn;

                $goods['sync_img'] = HelperStamp::delStamp($goods['sync_img'], Goods::SYNC_STATUS_TITLE_CN);*/

                $goods_keywords = str_replace('，',',',$goods_keywords);
                $goods_keywords = explode(',', $goods_keywords);
                $new_goods_keywords = [];
                foreach ($goods_keywords as $key_v) {
                    if (strpos($goods_short_name_cn, $key_v) === false) {
                        $new_goods_keywords[] = $key_v;
                    }
                }
                $goods_keywords = implode(',', $new_goods_keywords);

                $goods->goods_short_name_cn = $goods_short_name_cn;
                $goods->goods_keywords = $goods_keywords;
                $goods->save();
                /*GoodsOzon::updateAll(['status'=>0],['goods_no'=>$goods_no]);
                $goods_shop = GoodsShop::find()->where(['goods_no'=>$goods_no,'platform_type'=>Base::PLATFORM_OZON])->asArray()->all();
                foreach ($goods_shop as $goods_shop_v) {
                    GoodsEventService::addEvent($goods_shop_v['platform_type'], $goods_shop_v['shop_id'], $goods_shop_v['goods_no'], GoodsEvent::EVENT_TYPE_UPDATE_GOODS);
                }*/
            }catch (\Exception $e) {
                $errors[$i] = $e->getMessage();
                continue;
            }
            $success++;
        }

        if(!empty($errors)) {
            $lists = [];
            foreach ($errors as $i => $error) {
                $row = $data[$i];
                $info = [];
                $info['index'] = $i;
                $info['rvalue1'] = empty($row[$keyMap['goods_no']])?'':$row[$keyMap['goods_no']];
                $info['rvalue2'] = empty($row[$keyMap['sku_no']])?'':$row[$keyMap['sku_no']];
                $info['rvalue3'] = '';
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('商品关键字', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }

        return $this->FormatArray(self::REQUEST_SUCCESS, "导入成功", []);
    }


    /**
     * @routeName 导入价格
     * @routeDescription 导入价格
     * @return array
     * @throws
     */
    public function actionImportPrices()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $file = UploadedFile::getInstanceByName('file');
        if (!in_array($file->extension, ['xlsx', 'xls'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "只允许使用以下文件扩展名的文件：xlsx, xls。", []);
        }

        // 读取excel文件
        $data = Excel::import($file->tempName, [
            'setFirstRecordAsKeys' => false,
        ]);

        // 多Sheet
        if (isset($data[0])) {
            $data = $data[0];
        }

        $rowKeyTitles = [
            'sku_no' => 'SKU',
            'price' => '价格',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }


        if(empty($keyMap['sku_no']) || empty($keyMap['price'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }


        $count = count($data);
        $success = 0;
        $errors = [];
        for ($i = 2; $i <= $count; $i++) {
            $row = $data[$i];
            foreach ($row as &$rowValue) {
                $rowValue = !empty($rowValue) ? str_replace(' ', ' ', $rowValue) : '';
                $rowValue = !empty($rowValue) ? trim($rowValue) : '';
            }

            foreach (array_keys($rowKeyTitles) as $rowMapKey) {
                $rowKey = isset($keyMap[$rowMapKey]) ? $keyMap[$rowMapKey] : '';
                $$rowMapKey = isset($row[$rowKey]) ? trim($row[$rowKey]) : '';
            }

            if ((empty($sku_no) && empty($price))) {
                $errors[$i] = 'SKU和价格不能为空';
                continue;
            }

            try {
                $goods = GoodsChild::find()->where(['sku_no'=>$sku_no])->select('cgoods_no')->one();
                if(empty($goods)){
                    $errors[$i] = '商品为空';
                    continue;
                }
                (new GoodsService())->updateChildPrice($goods['cgoods_no'],['price'=>$price],'',true);
            }catch (\Exception $e) {
                $errors[$i] = $e->getMessage();
                continue;
            }
            $success++;
        }
        if(!empty($errors)) {
            $lists = [];
            foreach ($errors as $i => $error) {
                $row = $data[$i];
                $info = [];
                $info['index'] = $i;
                $info['rvalue1'] = empty($row[$keyMap['sku_no']])?'':$row[$keyMap['sku_no']];
                $info['rvalue2'] = empty($row[$keyMap['price']])?'':$row[$keyMap['price']];
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('价格', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "导入成功", []);
    }


    /**
     * @routeName 采购信息详情
     * @routeDescription 采购信息详情
     * @return array
     * @throws
     */
    public function actionViewOutsidePackage()
    {
        $req = Yii::$app->request;
        $goods_no = $req->get('goods_no');

        $where['goods_no'] = $goods_no;

        $outside_package_list = GoodsPackaging::find()->where(['goods_no' => $goods_no])->asArray()->all();
        $warehouse = [9999 => '全部'] + WarehouseService::getWarehouseMap();
        foreach ($outside_package_list as &$v) {
            $v['warehouse_name'] = $warehouse[$v['warehouse_id']];
        }

        $supplier_relationship_list = SupplierRelationship::find()->where(['goods_no' => $goods_no])->asArray()->all();
        $supplier = Supplier::allSupplierName();
        foreach ($supplier_relationship_list as &$v) {
            $v['supplier_name'] = empty($supplier[$v['supplier_id']]) ? '' : $supplier[$v['supplier_id']];
            $v['latest_transaction_date'] = $v['latest_transaction_date'] == 0 ? '' : date('Y-m-d H:i:s');
            $v['is_prior'] = $v['is_prior'] == 1 ? '是' : '否';
        }

        $goods_extend = GoodsExtend::find()->where(['goods_no' => $goods_no])->asArray()->one();

        return $this->render($this->render_view . 'view_outside_package', [
            'outside_package_list' => $outside_package_list,
            'supplier_relationship_list' => $supplier_relationship_list,
            'goods_no' => $goods_no,
            'goods_extend' => $goods_extend
        ]);
    }

    /**
     * @routeName 添加包装信息
     * @routeDescription 添加包装信息
     * @return array
     * @throws
     */
    public function actionCreatePackage()
    {
        $req = Yii::$app->request;
        $goods_no = $req->get('goods_no');
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new GoodsPackaging();
            $model->load($req->post(), '');
            $model['size'] = GoodsService::genSize($req->post());
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS,'添加成功',[]);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL,'添加失败',[]);
            }
        }
        return $this->render($this->render_view.'update_package',['goods_no' => $goods_no]);
    }

    /**
     * @routeName 修改包装信息
     * @routeDescription 修改包装信息
     * @return array
     * @throws
     */
    public function actionUpdatePackage()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $package = GoodsPackaging::findOne($id);
        $size = (new GoodsService())->getSizeArr($package['size']);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $model = GoodsPackaging::findOne($post['id']);
            $model->load($req->post(), '');
            $model['size'] = GoodsService::genSize($req->post());
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS,'修改成功',[]);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL,'修改失败',[]);
            }
        }
        return $this->render($this->render_view.'update_package',['package' => $package->toArray(),'size' => $size]);
    }

    /**
     * @routeName 删除包装信息
     * @routeDescription 删除包装信息
     * @return array
     * @throws
     */
    public function actionDeletePackage()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->get('id');
        $model = GoodsPackaging::findOne($id);
        if ($model->delete()) {
            return $this->FormatArray(self::REQUEST_SUCCESS,'删除成功',[]);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL,'删除失败',[]);
        }
    }

    /**
     * @routeName 编辑采购备注
     * @routeDescription 编辑采购备注
     * @return array
     * @throws
     */
    public function actionUpdatePurchaseDesc()
    {
        $req = Yii::$app->request;
        $goods_no = $req->get('goods_no');
        $goods = GoodsExtend::find()->where(['goods_no' => $goods_no])->one();
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $model = GoodsExtend::findOne(['goods_no' => $post['goods_no']]);
            if (empty($model)) {
                $model = new GoodsExtend();
                $model['goods_no'] = $post['goods_no'];
                $model['packages_num'] = 1;
                $model['warehouse_id'] = 0;
                $model['extend_param'] = '[]';
            }
            $model['purchase_desc'] = $post['purchase_desc'];
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS,'修改成功',[]);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL,'修改失败',[]);
            }
        }
        return $this->render($this->render_view . 'purchase_desc',['goods' => $goods,'goods_no' => $goods_no]);
    }


    /**
     * @routeName 商品销售情况详情
     * @routeDescription 商品销售情况详情
     * @return array
     * @throws
     */
    public function actionProductSalesView()
    {
        $req = Yii::$app->request;
        $warehouse_id = $req->get('warehouse_id');
        $cgoods_no = $req->get('cgoods_no');
        $where = [];
        $where['warehouse_id'] = $warehouse_id;
        $where['cgoods_no'] = $cgoods_no;
        $warehouse_product_sales = WarehouseProductSales::find()->where($where)->asArray()->one();
        return $this->render($this->render_view . 'view_product_sales',['data' => $warehouse_product_sales]);
    }


    /**
     * @routeName 多语言详情
     * @routeDescription 多语言详情
     * @return array
     * @throws
     */
    public function actionViewMultilingual()
    {
        $req = Yii::$app->request;
        $goods_no = $req->get('goods_no');
        $list = GoodsLanguage::find()->where(['goods_no' => $goods_no])->orderBy('id desc')->asArray()->all();

        $platform_information = PlatformInformation::find()->where(['goods_no' => $goods_no])->orderBy('id desc')->asArray()->all();
        $category_id = ArrayHelper::getColumn($platform_information,'o_category_name');
        $platform_categorys = PlatformCategory::find()->where(['id' => $category_id])->select(['name','name_cn','id'])->indexBy('id')->asArray()->all();

        foreach ($list as &$info) {
            $info['add_time'] = empty($info['add_time']) ? '' : date('Y-m-d H:i:s',$info['add_time']);
            $info['update_time'] = empty($info['update_time']) ? '' : date('Y-m-d H:i:s',$info['update_time']);
            $info['language_name'] = empty(CountryService::$goods_language[$info['language']]) ? '' : CountryService::$goods_language[$info['language']];
            $info['goods_image'] = GoodsImages::find()->alias('gi')
            ->leftJoin(Attachment::tableName().' at','at.id = gi.img_id')
            ->select('path')->where(['gi.goods_no' => $info['goods_no'],'gi.language' => $info['language']])->orderBy('sort asc')->scalar();
            $goods_translate_service = new GoodsTranslateService($info['language']);
            $goods_info = $goods_translate_service->getGoodsInfo($goods_no,'goods_name',GoodsTranslate::STATUS_MULTILINGUAL);
            $info['goods_name'] = empty($goods_info) ? '' : $goods_info['goods_name'];
        }
        foreach ($platform_information as &$info) {
            $info['add_time'] = empty($info['add_time']) ? '' : date('Y-m-d H:i:s',$info['add_time']);
            $info['update_time'] = empty($info['update_time']) ? '' : date('Y-m-d H:i:s',$info['update_time']);
            $info['platform_name'] = empty(Base::$platform_maps[$info['platform_type']]) ? '' : Base::$platform_maps[$info['platform_type']];
            $category = empty($platform_categorys[$info['o_category_name']]) ? [] : $platform_categorys[$info['o_category_name']];
            $info['category_name'] = empty($category) ? '' : $category['name'] . '(' . $category['name_cn'] . ')';
        }

        return $this->render($this->render_view.'view_multilingual',['goods_no' => $goods_no,'list' => $list,'platform_information' => $platform_information]);
    }


    /**
     * @routeName 多语言编辑
     * @routeDescription 多语言编辑
     * @return array
     * @throws
     */
    public function actionUpdateMultilingual()
    {
        $req = Yii::$app->request;
        $type = $req->get('type',3);//1为只修改语言，2为只修改分类，3为都修改
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $data = $req->post();
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $all_result = false;
                if(in_array($type,[1,3])) {
                    $result = (new GoodsService())->dealMultilingual($data);
                    $all_result = $result;
                }
                if(in_array($type,[2,3])) {//暂只支持俄语
                    if (isset($data['platform'])) {
                        $data['platform_type'] = $data['platform'];
                    }
                    $result = (new GoodsService())->dealPlatformCategoryProperties($data);
                    $all_result = $all_result?$all_result:$result;
                }
                if($all_result) {
                    $transaction->commit();
                    return $this->FormatArray(self::REQUEST_SUCCESS, "提交成功", []);
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, "提交失败", []);
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $transaction->rollBack();
                return $this->FormatArray(self::REQUEST_FAIL, "提交失败，".$error, []);
            }
        }

        $platform_type = $req->get('platform_type',0);
        $all_category = $req->get('all_category',0);
        $goods_no = $req->get('goods_no');
        $language = $req->get('language');

        $get_platform_type = $platform_type;
        if(!empty($language)) {
            if ($language == 'ru'){
                $platform_type = Base::PLATFORM_OZON;
            }
            if ($language == 'pl'){
                $platform_type = Base::PLATFORM_ALLEGRO;
            }
        } else {
            if($platform_type == Base::PLATFORM_OZON) {
                $language = 'ru';
            }
            if($platform_type == Base::PLATFORM_ALLEGRO) {
                $language = 'pl';
            }
        }
        $where['goods_no'] = $goods_no;
        $where['language'] = $language;
        $language_exists = GoodsLanguage::find()->where($where)->exists();

        $platform = [];
        if ($all_category == 1) {
            $platform = [0 => '无平台',Base::PLATFORM_OZON => 'Ozon',Base::PLATFORM_ALLEGRO => 'Allegro',Base::PLATFORM_WILDBERRIES => 'WB',Base::PLATFORM_AMAZON => 'Amazon'];
            unset($where['language']);
            $where['platform_type'] = $platform_type;
            $information_exists = PlatformInformation::find()->where($where)->exists();
            if (!$information_exists) {
                $goods_platform = PlatformInformation::find()->where(['goods_no' => $goods_no])->indexBy('platform_type')->asArray()->all();
                foreach ($goods_platform as $k => $v) { //删除已经存在的平台分类属性
                    if (array_key_exists($k,$platform)){
                        unset($platform[$k]);
                    }
                }
            }
        }

        $goods = Goods::find()->where(['goods_no' => $goods_no])->asArray()->one();
        $category_id = $goods['category_id'];
        $goods_language = CountryService::$goods_language;
        if (in_array($get_platform_type ,[Base::PLATFORM_OZON,Base::PLATFORM_ALLEGRO])) {
            $goods_language = [];
            if ($platform_type == Base::PLATFORM_OZON) {
                $goods_language['ru'] = '俄语';
            }
            if ($platform_type == Base::PLATFORM_ALLEGRO) {
                $goods_language['pl'] = '波兰语';
            }
        }

        $render_data = [
            'goods' => $goods,
            'platform_type' => $platform_type,
            'type' => $type,
            'goods_language' => $goods_language,
            'platform' => $platform,
        ];
        if ($language_exists) {
            $goods_own_image = GoodsImages::find()->alias('gi')
                ->select('at.id,at.path as img')
                ->leftJoin(Attachment::tableName().' at','at.id = gi.img_id')
                ->where(['gi.goods_no' => $goods_no,'gi.language' => $language])->orderBy('sort asc')->asArray()->all();
            $goods_own_image = json_encode($goods_own_image);
            if(in_array($type,[1,3])){
                $goods_translate_service = new GoodsTranslateService($language);
                $origin_name = $goods_translate_service->getGoodsInfo($goods_no, null , GoodsTranslate::STATUS_MULTILINGUAL);
                $origin_name['goods_name'] = !isset($origin_name['goods_name']) ? '' : $origin_name['goods_name'];
                $origin_name['goods_content'] = !isset($origin_name['goods_content']) ? '' : $origin_name['goods_content'];
                $origin_name['goods_desc'] = !isset($origin_name['goods_desc']) ? '' : $origin_name['goods_desc'];

                $render_data['goods_own_image'] = $goods_own_image;
                $render_data['origin_name'] = $origin_name;
                $render_data['language'] = $language;
                $render_data['goods_language'] = $goods_language;
                $video = GoodsLanguage::find()->where(['goods_no' => $goods_no,'language' => $language])->select('video')->scalar();
                $render_data['video'] = $video !== false ? $video : '';
            }
        }

        if(in_array($type,[2,3])) {
            $goods_information = PlatformInformation::find()->where(['goods_no' => $goods_no, 'platform_type' => $platform_type])->asArray()->one();
            $editor = '';
            $information_attribute_value = '';
            $specs_value = '';
            if (!empty($goods_information)) {
                $editor = EEditorService::restoreEditor($goods_information['editor_value']);
                $specs_value = json_decode($goods_information['specs_value'],true);
                $information_attribute_value = GoodsService::dealCategoryMapping($goods_information);
            }
            $mapping = CategoryMapping::find()->where(['category_id' => $category_id, 'platform_type' => $platform_type])->one();
            $mapping_attribute_value = GoodsService::dealCategoryMapping($mapping);

            $o_category_name = empty($mapping['o_category_name']) ? '' : $mapping['o_category_name'];
            $mapping_attribute_value = empty($mapping_attribute_value) ? '' : $mapping_attribute_value;

            $goods_platform_image = GoodsImages::find()->alias('gi')
                ->select('at.id,at.path as img')
                ->leftJoin(Attachment::tableName().' at','at.id = gi.img_id')
                ->where(['gi.goods_no' => $goods_no,'gi.platform_type' => $platform_type])->orderBy('sort asc')->asArray()->all();
            $goods_platform_image = json_encode($goods_platform_image);
            $goods = Goods::find()->where(['goods_no' => $goods_no])->asArray()->one();
            $weight = 0;
            if (!empty($goods)) {
                $weight = $goods['real_weight'] <= 0 ? $goods['weight'] : $goods['real_weight'];
            }

            $render_data['editor'] = $editor;
            $render_data['goods_information'] = $goods_information;
            $render_data['o_category_name'] = $o_category_name;
            $render_data['mapping_attribute_value'] = $mapping_attribute_value;
            $render_data['information_attribute_value'] = $information_attribute_value;
            $render_data['goods_platform_image'] = $goods_platform_image == '[]' ? '' : $goods_platform_image;
            $render_data['color'] = !isset($specs_value['color']) ? '' : $specs_value['color'];
            $render_data['weight'] = !isset($specs_value['weight']) ? $weight : $specs_value['weight'];
        }
        return $this->render($this->render_view.'update_multilingual',$render_data);
    }

    /**
     * @routeName 多语言删除
     * @routeDescription 多语言删除
     * @return array
     * @throws
     */
    public function actionDeleteMultilingual()
    {
        $req = Yii::$app->request;
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = $req->get('id');
        $goods_language = GoodsLanguage::findOne($id);
        $language = $goods_language['language'];
        $goods_images = GoodsImages::find()->where(['language' => $language,'goods_no' => $goods_language['goods_no']])->select('img_id')->asArray()->all();
        $img_ids = ArrayHelper::getColumn($goods_images,'img_id');
        if ($goods_language->delete()) {
            $goods_translate_service = new GoodsTranslateService($language);
            $goods_info = $goods_translate_service->getGoodsInfo($goods_language['goods_no'], null , GoodsTranslate::STATUS_MULTILINGUAL);
            foreach ($goods_info as $goods_field => $content) {
                $goods_translate_service->deleteGoodsInfo($goods_language['goods_no'], $goods_field, GoodsTranslate::STATUS_MULTILINGUAL);
            }
            GoodsImages::deleteAll(['goods_no' => $goods_language['goods_no'],'language' => $language]);
            Attachment::deleteAll(['id' => $img_ids]);
            if ($language == 'ru' || $language == 'pl') {
                $platform_type = $language == 'ru' ? Base::PLATFORM_OZON : Base::PLATFORM_ALLEGRO;
                FindGoods::deleteAll(['goods_no' => $goods_language['goods_no'],'overseas_goods_status' => $platform_type,'platform_type' => $platform_type]);
                //PlatformInformation::deleteAll(['goods_no' => $goods_language['goods_no'], 'platform_type' => $platform_type]);
            }
            return $this->FormatArray(self::REQUEST_SUCCESS,'删除成功',[]);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL,'删除失败',[]);
        }
    }

    /**
     * @routeName 删除分类属性
     * @routeDescription 删除分类属性
     * @return array
     * @throws
     */
    public function actionDeleteInformation()
    {
        $req = Yii::$app->request;
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = $req->get('id');
        $platform_information = PlatformInformation::find()->where(['id' => $id])->one();
        if ($platform_information['platform_type'] == Base::PLATFORM_WILDBERRIES) {
            $goods_images = GoodsImages::find()->where(['platform_type' => Base::PLATFORM_WILDBERRIES,'goods_no' => $platform_information['goods_no']])->select('img_id')->asArray()->all();
            $img_ids = ArrayHelper::getColumn($goods_images,'img_id');
            GoodsImages::deleteAll(['goods_no' => $platform_information['goods_no'],'platform_type' => Base::PLATFORM_WILDBERRIES]);
            Attachment::deleteAll(['id' => $img_ids]);
        }
        if ($platform_information->delete()) {
            return $this->FormatArray(self::REQUEST_SUCCESS,'删除成功',[]);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL,'删除失败',[]);
        }
    }

    /**
     * @routeName 编辑器
     * @routeDescription 编辑器
     */
    public function actionEditor()
    {
        $req = Yii::$app->request;
        $goods_no = $req->get('goods_no');
        $platform_type = $req->get('platform_type');
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $data = json_encode($post,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $this->FormatArray(self::REQUEST_SUCCESS, "保存成功", $data);
        }
        return $this->render($this->render_view.'editor',['platform_type' => $platform_type, 'goods_no' => $goods_no]);
    }


     /* @routeName 在途数详情
     * @routeDescription 在途数详情
     * @return array
     * @throws
     */
    public function actionTransitQuantityView()
    {
        $req = Yii::$app->request;
        $warehouse_id = $req->get('warehouse_id');
        $cgoods_no = $req->get('cgoods_no');
        $where['blg.warehouse_id'] = $warehouse_id;
        $where['blg.cgoods_no'] = $cgoods_no;
        $where['blg.status'] = BlContainer::STATUS_NOT_DELIVERED;
        $transit = BlContainerGoods::find()->alias('blg')
            ->select('bl.bl_no,bl.initial_number,bl.size,bl.weight,blg.num,blg.price,blt.track_no,blt.cjz,blt.delivery_time,blt.arrival_time')
            ->leftJoin(BlContainer::tableName().' bl','blg.bl_id = bl.id')
            ->leftJoin(BlContainerTransportation::tableName().' blt','blt.id = bl.bl_transportation_id')
            ->where($where)->asArray()->all();
        $list = [];
        foreach ($transit as $k => &$v) {
            $v['cjz'] = round(GoodsService::cjzWeight($v['size'],$v['cjz']),2);
            $v['delivery_time'] = empty($v['delivery_time']) ? '' : date('Y-m-d',$v['delivery_time']);
            $v['arrival_time'] = empty($v['arrival_time']) ? '' : date('Y-m-d',$v['arrival_time']);
            $list[$v['track_no']][$k] = $v;
        }
        return $this->render($this->render_view.'view_transit_quantity',['list' => $list]);
    }


    /**
     * @routeName 库存数详情
     * @routeDescription 库存数详情
     * @return array
     * @throws
     */
    public function actionStockView()
    {
        $req = Yii::$app->request;
        $cgoods_no = $req->get('cgoods_no');
        $warehouse_id = $req->get('warehouse_id');

        $where['cgoods_no'] = $cgoods_no;
        $where['warehouse'] = $warehouse_id;
        $where['status'] = 2;

        $now_time = strtotime(date('Y-m-d',time()));
        $third_time = GoodsService::getBeforeTime('-30');
        $sixty_time = GoodsService::getBeforeTime('-60');
        $ninety_time = GoodsService::getBeforeTime('-90');
        $hundred_eighty_time = GoodsService::getBeforeTime('-180');
        $three_hundred_sixty_time = GoodsService::getBeforeTime('-360');

        $select[] = GoodsService::combinationSql($third_time,$now_time,'less_thirty','inbound_time');
        $select[] = GoodsService::combinationSql($sixty_time,$third_time,'greater_thirty','inbound_time');
        $select[] = GoodsService::combinationSql($ninety_time,$sixty_time,'greater_sixty','inbound_time');
        $select[] = GoodsService::combinationSql($hundred_eighty_time,$ninety_time,'greater_ninety','inbound_time');
        $select[] = GoodsService::combinationSql($three_hundred_sixty_time,$hundred_eighty_time,'greater_hundred_eighty','inbound_time');
        $select[] = 'sum(case when inbound_time < '.$three_hundred_sixty_time.' then 1 else 0 end) as greater_three_hundred_sixty';
        $select = implode(',',$select);
        $stock_details = GoodsStockDetails::find()->where($where)->andWhere(['=','outgoing_time',0])->select($select)->asArray()->one();
        unset($where['status']);
        $stock = GoodsStock::find()->where($where)->select('real_num')->scalar();

        return $this->render($this->render_view.'view_stock',['stock_details' => $stock_details,'stock' => $stock]);
    }


    /**
     * @routeName 编辑器复制json
     * @routeDescription 编辑器复制json
     * @return array
     * @throws
     */
    public function actionEditorJson()
    {
        $req = Yii::$app->request;
        Yii::$app->response->format = Response::FORMAT_JSON;
        $post = $req->post();
        $post = json_encode($post,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $editor = EEditorService::dealEditor($post);
        $list = EEditorService::platformJson($editor);
        if (!empty($list)) {
            return $this->FormatArray(self::REQUEST_SUCCESS,'',$list);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL,'转化json失败',[]);
        }
    }

    /**
     * @routeName 获取平台分类属性
     * @routeDescription 获取平台分类属性
     * @return array |Response|string
     * @throws NotFoundHttpException
     */
    public function actionGetInformationAttribute()
    {
        $req = Yii::$app->request;
        Yii::$app->response->format = Response::FORMAT_JSON;
        $post = $req->post();
        $platform_type = '';
        if ($post['language'] == 'ru') {
            $platform_type = Base::PLATFORM_OZON;
        }
        $goods_information = PlatformInformation::find()->where(['goods_no' => $post['goods_no'],'platform_type' => $platform_type])->asArray()->one();
        if (empty($goods_information)) {
            return $this->FormatArray(self::REQUEST_FAIL,'',[]);
        }
        $editor = EEditorService::restoreEditor($goods_information['editor_value']);
        $information_attribute_value = GoodsService::dealCategoryMapping($goods_information);
        $o_category_name = $goods_information['o_category_name'];
        $data['editor'] = empty($editor) ? '' : json_encode($editor,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $data['goods_information'] = $goods_information;
        $data['o_category_name'] = $o_category_name;
        $data['information_attribute_value'] = $information_attribute_value;
        return $this->FormatArray(self::REQUEST_SUCCESS,'',$data);
    }

    /**
     * @routeName 更新状态
     * @routeDescription 更新状态
     * @return array |Response|string
     * @throws NotFoundHttpException
     */
    /*public function actionUpdateStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');

        $goods_model = $this->findModel($id);
        $goods_model->status = $goods_model->status == 10 ? 0 : 10;
        if ($goods_model->save()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "更新失败", []);
        }
    }*/

    /**
     * @routeName 获取商品自有图片或图片
     * @routeDescription 获取商品自有图片或图片
     * @return array |Response|string
     * @throws NotFoundHttpException
     */
    public function actionGetGoodsImage()
    {
        $req = Yii::$app->request;
        $goods_no = $req->get('goods_no');
        $image_list = [];
        $goods_images = GoodsImages::find()->alias('gi')
            ->leftJoin(Attachment::tableName().' at','at.id = gi.img_id')
            ->where(['gi.language' => 'pl','gi.goods_no' => $goods_no])
            ->select('gi.img_id,at.path')->orderBy('gi.sort asc')->asArray()->all();
        if (empty($goods_images)) {
            $goods_images = Goods::find()->where(['goods_no' => $goods_no])->select('goods_img')->scalar();
            $image_list = $goods_images;
            $image_list = json_decode($image_list,true);
        } else {
            foreach ($goods_images as $k => $v) {
                $image_list[$k]['img'] = $v['path'];
            }
        }
        return $this->render($this->render_view.'select_image',['image_list' => $image_list]);
    }

    /**
     * @param $id
     * @return null|Goods
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = Goods::findOne($id)) !== null) {
           return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }


}