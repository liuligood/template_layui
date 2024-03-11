<?php

namespace backend\controllers;

use common\base\BaseController;
use common\components\statics\Base;
use common\models\goods\GoodsStock;
use common\models\Order;
use common\models\warehousing\Warehouse;
use common\models\warehousing\WarehouseProvider;
use common\services\sys\CountryService;
use common\services\warehousing\WarehouseService;
use Yii;
use backend\models\search\WarehouseSearch;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class WarehouseController extends BaseController
{
    public function model()
    {
        return new Warehouse();
    }


    /**
     * @routeName 供应商仓库主页
     * @routeDescription 供应商仓库主页
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $warehouse_provider_id = $req->get('warehouse_provider_id');
        return $this->render('index',['warehouse_provider_id' => $warehouse_provider_id]);
    }

    /**
     * @routeName 供应商仓库列表
     * @routeDescription 供应商仓库列表
     */
    public function actionList()
    {
        $req = Yii::$app->request;
        $warehouse_provider_id = $req->get('warehouse_provider_id');
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel =new WarehouseSearch();
        $params = Yii::$app->request->queryParams;
        $params['WarehouseSearch']['warehouse_provider_id'] = $warehouse_provider_id;
        $where = $searchModel->search($params);
        $data = $this->lists($where);
        foreach ($data['list'] as &$info){
            $info['add_time'] = Yii::$app->formatter->asDatetime($info['add_time']);
            $info['update_time'] = Yii::$app->formatter->asDatetime($info['update_time']);
            $info['status'] = WarehouseProvider::$status_maps[$info['status']];
            $info['platform_type'] = $info['platform_type'] == 0 ? '' : Base::$platform_maps[$info['platform_type']];
            $info['exists_order'] = Order::find()->where(['warehouse' => $info['id']])->exists();
            $info['exists_stock'] = GoodsStock::find()->where(['warehouse' => $info['id']])->exists();
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data['list'],$data['pages']->totalCount);
    }


    /**
     * @routeName 新增供应商仓库
     * @routeDescription 创建供应商仓库
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $warehouse_provider_id = $req->get('warehouse_provider_id');
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $model = new Warehouse();
            $model->warehouse_provider_id = $post['warehouse_provider_id'];
            $model->warehouse_name = $post['warehouse_name'];
            $model->warehouse_code = $post['warehouse_code'];
            $model->country = $post['country'];
            $model->status = $post['status'];
            $model->platform_type = empty($post['platform_type']) ? 0 : $post['platform_type'];
            $model->eligible_country = isset($post['eligible_country']) ? implode(',',$post['eligible_country']) : '';
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, "添加失败", []);
            }
        }
        return $this->render('create',['warehouse_provider_id'=>$warehouse_provider_id]);
    }

    /**
     * @routeName 更新供应商仓库
     * @routeDescription 更新供应商仓库
     * @throws
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $warehouse_provider_id = $req->get('warehouse_provider_id');
        if ($req->isPost) {
            $id = $req->post('id');
        }
        $model = $this->findModel($id);
        $model['eligible_country'] = empty($model['eligible_country']) ? '' : explode(',',$model['eligible_country']);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $model->warehouse_provider_id = $post['warehouse_provider_id'];
            $model->warehouse_name = $post['warehouse_name'];
            $model->warehouse_code = $post['warehouse_code'];
            $model->country = $post['country'];
            $model->status = $post['status'];
            $model->platform_type = empty($post['platform_type']) ? 0 : $post['platform_type'];
            $model->eligible_country = isset($post['eligible_country']) ? implode(',',$post['eligible_country']) : '';
            if ($model->save()) {
                WarehouseService::clearInfoCache($id);
                return $this->FormatArray(self::REQUEST_SUCCESS, "修改成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, "修改失败", []);
            }
        } else {
            return $this->render('update',['info' => $model,'warehouse_provider_id' => $warehouse_provider_id]);
        }
    }

    /**
     * @routeName 删除供应商仓库
     * @routeDescription 删除供应商仓库
     * @return array
     * @throws
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $model = $this->findModel($id);
        $exists_order = Order::find()->where(['warehouse' => $id])->exists();
        $exists_stock = GoodsStock::find()->where(['warehouse' => $id])->exists();
        if ($exists_order === true || $exists_stock === true) {
            return $this->FormatArray(self::REQUEST_FAIL, "删除失败", []);
        }
        if ($model->delete()) {
            WarehouseService::clearInfoCache($id);
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "删除失败", []);
        }
    }


    /**
     * @routeName 供应商仓库详情
     * @routeDescription 供应商仓库详情
     * @throws
     */
    public function actionView()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $model = $this->findModel($id);
        $country_all = CountryService::getSelectOption();
        $eligible_country_list = [];
        $eligible_country = explode(',',$model['eligible_country']);
        foreach ($eligible_country as $v) {
            $eligible_country_list[] = empty($country_all[$v]) ? $v : $country_all[$v];
        }
        $eligible_country_list = implode(',',$eligible_country_list);
        $country = empty($country_all[$model['country']]) ? $model['country'] : $country_all[$model['country']];
        return $this->render('view',[
            'info' => $model->toArray(),
            'country' => $country,
            'eligible_country' => $eligible_country_list
        ]);
    }

    /**
     * Finds the Warehouse model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Warehouse the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Warehouse::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
