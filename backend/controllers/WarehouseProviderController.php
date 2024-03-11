<?php

namespace backend\controllers;

use backend\models\search\WarehouseProviderSearch;
use common\base\BaseController;
use common\components\statics\Base;
use common\models\warehousing\Warehouse;
use common\models\warehousing\WarehouseProvider;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;


class WarehouseProviderController extends BaseController
{
    public function model()
    {
        return new WarehouseProvider();
    }

    /**
     * @routeName 仓库供应商主页
     * @routeDescription 仓库供应商主页
     * @throws
     * @return string |Response |array
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * @routeName 仓库供应商列表
     * @routeDescription 仓库供应商列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel=new WarehouseProviderSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);
        foreach ($data['list'] as &$info){
            $info['add_time'] = Yii::$app->formatter->asDatetime($info['add_time']);
            $info['update_time'] = Yii::$app->formatter->asDatetime($info['update_time']);
            $info['status'] = WarehouseProvider::$status_maps[$info['status']];
            $info['warehouse_provider_type'] = WarehouseProvider::$type_maps[$info['warehouse_provider_type']];
            $info['exists'] = Warehouse::find()->where(['warehouse_provider_id' => $info['id']])->exists();
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data['list'],$data['pages']->totalCount);
    }


    /**
     * @routeName 新增仓库供应商
     * @routeDescription 创建仓库供应商
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $model = new WarehouseProvider();
            $model->warehouse_provider_name = $post['warehouse_provider_name'];
            $model->status = $post['status'];
            $model->warehouse_provider_type = $post['warehouse_provider_type'];
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, "添加失败", []);
            }
        }
        return $this->render('create');
    }

    /**
     * @routeName 更新仓库供应商
     * @routeDescription 更新仓库供应商
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
            $post = $req->post();
            $model->warehouse_provider_name = $post['warehouse_provider_name'];
            $model->status = $post['status'];
            $model->warehouse_provider_type = $post['warehouse_provider_type'];
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "修改成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, "修改失败", []);
            }
        } else {
            return $this->render('update',['info' => $model]);
        }
    }

    /**
     * @routeName 删除仓库供应商
     * @routeDescription 删除仓库供应商
     * @return array
     * @throws
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $model = $this->findModel($id);
        $exists = Warehouse::find()->where(['warehouse_provider_id' => $model['id']])->exists();
        if ($exists) {
            return $this->FormatArray(self::REQUEST_FAIL, "删除失败", []);
        }
        if ($model->delete()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "删除失败", []);
        }
    }

    /**
     * Finds the Owp model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = WarehouseProvider::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
