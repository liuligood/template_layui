<?php

namespace backend\controllers;

use common\base\BaseController;
use common\services\goods\GoodsService;
use Yii;
use common\models\SupplierRelationship;
use yii\web\NotFoundHttpException;
use yii\web\Response;


class SupplierRelationshipController extends BaseController
{
    /**
     * @routeName 添加供货关系
     * @routeDescription 添加供货关系
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $goods_no = $req->get('goods_no');
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new SupplierRelationship();
            $model->load($req->post(), '');
            if ($model->save()) {
                (new GoodsService())->updatePlatformGoods($model->goods_no,true);
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, '添加失败', []);
            }
        }
        return $this->render('update',['goods_no' => $goods_no]);
    }

    /**
     * @routeName 更新供货关系
     * @routeDescription 更新供货关系
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
            $model->load($req->post(), '');
            if ($model->save()) {
                (new GoodsService())->updatePlatformGoods($model->goods_no,true);
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, '更新失败', []);
            }
        } else {
            return $this->render('update', ['info' => $model->toArray()]);
        }
    }


    /**
     * @routeName 删除供货关系
     * @routeDescription 删除供货关系
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $model = $this->findModel($id);
        $goods_no = $model->goods_no;
        if ($model->delete()) {
            (new GoodsService())->updatePlatformGoods($goods_no,true);
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除失败", []);
        }
    }

    /**
     * Finds the SupplierRelationship model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return SupplierRelationship the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = SupplierRelationship::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
