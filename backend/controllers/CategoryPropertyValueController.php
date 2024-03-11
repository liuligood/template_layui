<?php

namespace backend\controllers;

use common\models\Category;
use common\models\CategoryProperty;
use common\models\GoodsProperty;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use common\base\BaseController;
use Yii;
use common\models\CategoryPropertyValue;
use backend\models\search\CategoryPropertyValueSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;


class CategoryPropertyValueController extends BaseController
{
    public function model()
    {
        return new CategoryPropertyValue();
    }

    public function actionIndex()
    {
        $req = Yii::$app->request;
        $property_id = $req->get('property_id');
        return $this->render('index', ['property_id' => $property_id]);
    }

    /**
     * @routeName 属性值列表
     * @routeDescription 属性值列表
     */
    public function actionList()
    {
        $req = Yii::$app->request;
        $property_id = $req->get('property_id');
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel=new CategoryPropertyValueSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams,$property_id);
        $data = $this->lists($where);
        foreach ($data['list'] as &$info){
            $info['status'] = empty(CategoryPropertyController::$map_tre[$info['status']]) ? '' : CategoryPropertyController::$map_tre[$info['status']];
            $info['add_time'] = $info['add_time'] == 0 ? '' : date('Y-m-d H:i:s', $info['add_time']);
            $info['update_time'] = $info['update_time'] == 0 ? '' : date('Y-m-d H:i:s', $info['update_time']);
            $info['exists'] = GoodsProperty::find()->where(['property_value_id' => $info['id']])->exists();
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data['list'],$data['pages']->totalCount);
    }

    /**
     * @routeName 类目属性值的增加
     * @routeDescription 类目属性值的增加
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $property_id = $req->get('property_id');
        if ($req->isPost) {
            $post = $req->post();
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new CategoryPropertyValue();
            $model->load($req->post(), '');
            $exists = CategoryPropertyValue::find()->where(['property_id'=>$model->property_id,'property_value' => $post['property_value']])->exists();
            if ($exists) {
                return $this->FormatArray(self::REQUEST_FAIL,'该属性值已存在', []);
            }
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        }
        return $this->render('create',['property_id'=>$property_id]);
    }

    /**
     * @routeName 更新属性值
     * @routeDescription 更新属性值
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
            if ($model['property_value'] != $post['property_value']) {
                $exists = CategoryPropertyValue::find()->where(['property_id'=>$model->property_id,'property_value' => $post['property_value']])->exists();
                if ($exists) {
                    return $this->FormatArray(self::REQUEST_FAIL,'该属性值已存在', []);
                }
            }
            $model->load($req->post(), '');
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, '更新失败', []);
            }
        } else {
            return $this->render('update', ['info' => $model->toArray()]);
        }
    }

    /**
     * @routeName 属性值删除
     * @routeDescription 属性值删除
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $model = $this->findModel($id);
        $exists = GoodsProperty::find()->where(['property_value_id' => $id])->exists();
        if ($exists) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除失败", []);
        }
        if ($model->delete()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除失败", []);
        }
    }

    /**
     * Finds the CategoryPropertyValue model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CategoryPropertyValue the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CategoryPropertyValue::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
