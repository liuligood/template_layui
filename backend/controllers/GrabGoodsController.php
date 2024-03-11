<?php

namespace backend\controllers;

use backend\models\search\GrabGoodsSearch;
use common\models\Goods;
use common\models\grab\GrabGoods;
use common\services\FGrabService;
use common\services\goods\GoodsService;
use Yii;
use common\base\BaseController;
use yii\web\Response;
use yii\web\NotFoundHttpException;

class GrabGoodsController extends BaseController
{
    /**
     * 获取model
     * @return \common\models\BaseAR
     */
    public function model(){
        return new GrabGoods();
    }

    /**
     * @routeName 亚马逊商品管理
     * @routeDescription 亚马逊商品管理
     */
    public function actionIndex()
    {
        $queryParams = Yii::$app->request->queryParams;
        $gid = empty($queryParams['gid'])?'':$queryParams['gid'];
        return $this->render('index',['gid'=>$gid]);
    }

    /**
     * @routeName 亚马逊商品列表
     * @routeDescription 亚马逊商品列表
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $searchModel = new GrabGoodsSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where,'use_time desc');

        $lists = array_map(function ($info) {
            $info['use_time'] = date('Y-m-d H:i',$info['use_time']);
            $info['check_stock_time'] = date('Y-m-d H:i',$info['check_stock_time']);
            $info['source_desc'] = empty(FGrabService::$source_map[$info['source']]) ? '' : FGrabService::$source_map[$info['source']]['name'];
            $info['use_status_desc'] = empty(GrabGoods::$use_status_map[$info['use_status']]) ? '' : GrabGoods::$use_status_map[$info['use_status']];
            unset($info['desc']);
            unset($info['desc2']);
            return $info;
        }, $data['list']);

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $data['pages']->totalCount
        );
    }

    /**
     * @routeName 更新亚马逊商品
     * @routeDescription 更新亚马逊商品信息
     * @throws
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            $id = $req->post('id');
        }
        $model = $this->findModel($id);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $data = $req->post();
            $data = $this->dataDeal($data);
            if(!empty($model->use_time)){
                unset($data['use_time']);
            }
            if ($model->load($data, '') == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }

            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        } else {
            return $this->render('update', ['model' => $model]);
        }
    }

    /**
     *
     * @param $data
     * @return mixed
     */
    private function dataDeal($data){
        $use_status = $data['use_status'];
        if($use_status == GrabGoods::USE_STATUS_INVALID || $use_status == GrabGoods::USE_STATUS_VALID) {
            $data['use_time'] = time();
        }
        //$data['desc'] = $data['desc1'] . $data['desc2'];
        return $data;
    }

    /**
     * @routeName 批量提交到商品库
     * @routeDescription 批量提交到商品库
     * @return array
     * @throws
     */
    public function actionBatchAddAmazon()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->post('id');
        try {
            foreach ($id as $v) {
                (new GoodsService())->claimAmazon($v);
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "提交成功", []);
            /*
            $result = (new GoodsService())->claimAmazon($id);
            if ($result) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "提交成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, "提交失败", []);
            }*/
        } catch (\Exception $e) {
            return $this->FormatArray(self::REQUEST_FAIL, "提交失败:" . $e->getMessage(), []);
        }
    }

    /**
     * @routeName 认领商品
     * @routeDescription 认领指定商品
     * @return array
     * @throws
     */
    /*public function actionClaim()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            if (empty($post['shop'])) {
                return $this->FormatArray(self::REQUEST_FAIL, "认领店铺不能为空", []);
            }

            try {
                $result = (new GoodsService())->claimAmazon($id, $post['shop']);

                if ($result) {
                    return $this->FormatArray(self::REQUEST_SUCCESS, "认领成功", []);
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, "认领失败", []);
                }
            } catch (\Exception $e) {
                return $this->FormatArray(self::REQUEST_FAIL, "认领失败:" . $e->getMessage(), []);
            }

        } else {
            //$shop_ids = GoodsShop::find()->where(['goods_no' => $goods_no])->select(['shop_id'])->column();
            $shop_ids = [];
            $platform = [];
            foreach (GoodsService::$amazon_platform_type as $k => $v) {
                $shop = Shop::find()->where(['platform_type' => $k])->select(['id', 'name'])->asArray()->all();
                $shop = ArrayHelper::map($shop, 'id', 'name');
                $platform[$k] = [
                    'name' => $v,
                    'shop' => $shop
                ];
            }
            return $this->render('claim', ['id' => $id, 'platform' => $platform, 'shop_ids' => $shop_ids]);
        }
    }*/

    /**
     * @routeName 批量设置状态
     * @routeDescription 批量设置状态
     * @return array |Response|string
     */
    public function actionBatchUpdateUseStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->post('id');
        $use_status = $req->post('use_status');
        if (GrabGoods::updateAll(['use_status'=>$use_status],['id'=>$id])) {
            if($use_status == GrabGoods::USE_STATUS_INVALID || $use_status == GrabGoods::USE_STATUS_VALID) {
                GrabGoods::updateAll(['use_time' => time()], ['id' => $id,'use_time'=>0]);
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "更新失败", []);
        }
    }

    /**
     * @routeName 批量设置类目
     * @routeDescription 批量设置类目
     * @return array |Response|string
     */
    public function actionBatchUpdateCategory()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->post('id');
        $category = $req->post('category');
        if (GrabGoods::updateAll(['category'=>trim($category)],['id'=>$id])) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "设置成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "设置失败", []);
        }
    }



    /**
     * @param $id
     * @return null|Goods
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = GrabGoods::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * @routeName 亚马逊商品导出
     * @routeDescription 亚马逊商品导出
     * @return array |Response|string
     */
    public function actionExport()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $search_model = new GrabGoodsSearch();
        $where = $search_model->search(Yii::$app->request->queryParams);
        //$where['use_status'] = GrabGoods::USE_STATUS_VALID;
        $list = GrabGoods::getAllByCond($where);
        $data = $search_model->export($list);

        return $this->FormatArray(self::REQUEST_SUCCESS, "", $data);
    }
}