<?php

namespace backend\controllers;

use common\components\HelperStamp;
use common\models\financial\CollectionAccount;
use common\models\financial\CollectionBankCards;
use common\models\platform\PlatformShopConfig;
use common\services\warehousing\WarehouseService;
use Yii;
use common\models\Shop;
use backend\models\search\ShopSearch;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use common\base\BaseController;
use yii\helpers\ArrayHelper;
use common\components\statics\Base;
use common\models\User;
use common\models\Goods;
use common\components\CommonUtil;
use common\services\sys\SystemOperlogService;
use common\services\goods\GoodsService;
use common\models\sys\SystemOperlog;



/**
 * ShopController implements the CRUD actions for Shop model.
 */
class ShopController extends BaseController
{
    
   public function model()
   {
       return new Shop();
   }

    /**
     * {@inheritdoc}
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
    
    /**
     * @routeName Shop列表
     * @routeDescription Shop列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON; 
        $searchModel=new ShopSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where,'id desc');
        $lists = array_map(function ($info) {
            $info['add_time'] = Yii::$app->formatter->asDatetime($info['add_time']);
            $info['update_time'] = Yii::$app->formatter->asDatetime($info['update_time']);
            $info['platform_type']=Base::$platform_maps[$info['platform_type']];
            $info['status'] = Shop::$status_maps[$info['status']];
            $info['admin_id'] = User::getInfoNickname($info['admin_id']);
            $info['sales_status'] = empty($info['sale_status']) ? '' : Shop::$sale_status_maps[$info['sale_status']];
            $info['last_order_time'] = $info['last_order_time'] == 0 ? '' : Yii::$app->formatter->asDatetime($info['last_order_time']);
            $arr_assignment = (new HelperStamp(Shop::$api_assignment_maps))->getMap($info['api_assignment']);
            $info['api_assignment'] = empty($arr_assignment) ? '' : implode(',',$arr_assignment);
            return $info;
        }, $data['list']);
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$lists,$data['pages']->totalCount);
    }
    
    /**
     * @routeName 新增Shop
     * @routeDescription 创建新的Shop
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $collection = CollectionAccount::getRelevancyAccount();
        $bank_cards = CollectionBankCards::getListBank();
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $model = new Shop();
            if (isset($post['assignment'])) {
                $model->api_assignment = array_sum($post['assignment']);
            }
            $model->load($req->post(),'');
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        }
        $id = $req->get('shop_id');
        $model = new Shop();
        if(!empty($id)){
            $model = $this->findModel($id);
            $model->id = '';
        }
        return $this->render('update', ['info' => $model,
            'warehouse_lists' => [],
            'collection' => $collection,
            'bank_cards' => $bank_cards,
            'arr_assignment' => []
        ]);


    }
    
    /**
     * @routeName 更新Shop
     * @routeDescription 更新Shop信息
     * @throws
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $id = $req->get('shop_id');
        $collection = CollectionAccount::getRelevancyAccount();
        $bank_cards = CollectionBankCards::getListBank();
        if ($req->isPost) {
            $id = $req->post('shop_id');
        }
        $model = $this->findModel($id);
        $arr_assignment = (new HelperStamp(Shop::$api_assignment_maps))->getMap($model['api_assignment']);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            if ($model->load($req->post(), '') == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }
            $bank_cards_id = $req->post('collection_bank_cards_id');
            if (!isset($bank_cards_id)){
                $model->collection_bank_cards_id = 0;
            }
            $model->api_assignment = 0;
            if (isset($post['assignment'])) {
                $model->api_assignment = array_sum($post['assignment']);
            }
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        } else {
            $warehouse_lists = WarehouseService::getOverseasWarehouse();
            return $this->render('update', ['info' => $model,
                'warehouse_lists' => $warehouse_lists,
                'collection' => $collection,
                'bank_cards' => $bank_cards,
                'arr_assignment' => $arr_assignment
            ]);
        }
    }
    
    
    /**
     * @routeName 批量更新负责人信息
     * @routeDescription 批量更新负责人信息
     * @throws
     */
    public function actionUpdates()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();            
            $admin_id = $post['admin_id'];
            $admin_arr = explode(',',$admin_id);
            $admin_id = (int)end($admin_arr);            
            if(empty($admin_id)) {
                return $this->FormatArray(self::REQUEST_FAIL, "店铺负责人不能为空", []);
            }
            if(!empty($id)) {
                $shop_list = Shop::find()->where(['id' => explode(',', $id)])->select(['id','admin_id'])->all();;
            }
            foreach ($shop_list as $shop_v){
                $shops = $shop_v['id'];
                $model = $this->findModel($shops);            
            if ($model->load($req->post(), '') == false) {
                    return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }
            if ($model->save()){    
            }else {
                return $this->FormatArray(self::REQUEST_FAIL, "设置店铺负责人失败", []);
            }                                       
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "设置店铺负责人成功", []);
        }else {
            return $this->render('updates');
        }
    }
    
    /**
     * @routeName 删除Shop
     * @routeDescription 删除指定Shop
     * @return array
     * @throws
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('shop_id');
        $model = $this->findModel($id);
        if ($model->delete()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除失败", []);
        }
    }
    
    public function actionView()
    {
        $req=Yii::$app->request;
        $id = $req->get("shop_id");
        $sales_id = $req->get("sales_id");
        $shopModel=$this->findModel($id);
        $arr_assignment = (new HelperStamp(Shop::$api_assignment_maps))->getMap($shopModel['api_assignment']);
        $assignment = empty($arr_assignment) ? '' : implode(',',$arr_assignment);
        $info=$shopModel->toArray();
        $warehousemodel = PlatformShopConfig::find()->where(['shop_id'=>$id])->asArray()->all();
        $info['collection_platform'] = $info['collection_platform'] == 0? '' : Shop::$collection_maps[$info['collection_platform']];
        if (in_array($info['platform_type'], [Base::PLATFORM_TIKTOK, Base::PLATFORM_ALLEGRO,Base::PLATFORM_MERCADO,Base::PLATFORM_MICROSOFT])) {
            $platform_name = strtolower(Base::$platform_maps[$info['platform_type']]);
            $info['auth_url'] = 'https://www.sanlindou.com/auth/'.$platform_name.'_'.$id;
        }
        return $this->render('view',['info'=>$info,'sales_id' => $sales_id,'ware'=>$warehousemodel,'assignment' => $assignment]);
    }
    /**
     * @param $id
     * @return null|Demo
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = Shop::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
