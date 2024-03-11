<?php

namespace backend\controllers;

use common\base\BaseController;
use Yii;
use common\models\TransportProviders;
use backend\models\search\TransportProvidersSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * TransportProvidersController implements the CRUD actions for TransportProviders model.
 */
class TransportProvidersController extends BaseController
{

    public function model()
    {
        return new TransportProviders();
    }

    /**
     * @routeName 物流主页
     * @routeDescription 物流主页
     */
    public function actionIndex()
    {
        return $this->render('index');
    }


    /**
     * @routeName 物流列表
     * @routeDescription 物流列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel=new TransportProvidersSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);
        $lists = array_map(function ($info) {
            $info['add_time'] = Yii::$app->formatter->asDatetime($info['add_time']);
            $info['update_time'] = Yii::$app->formatter->asDatetime($info['update_time']);
            $info['status'] = TransportProviders::$status_maps[$info['status']];
            return $info;
        }, $data['list']);
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$lists,$data['pages']->totalCount);
    }

    /**
     * @routeName 新增物流方式
     * @routeDescription 新增物流方式
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new TransportProviders();
            $model->load($req->post(), '');
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        }
        return $this->render('create');
    }

    /**
     * @routeName 更新物流
     * @routeDescription 更新物流
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
            if ($model->load($req->post(), '') == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }

            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        } else {
            return $this->render('update', ['model' => $model->toArray()]);
        }
    }

    /**
     * @routeName 删除物流
     * @routeDescription 删除指定物流
     * @return array
     * @throws
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $model = $this->findModel($id);
        if ($model->delete()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除失败", []);
        }
    }


    protected function findModel($id)
    {
        if (($model = TransportProviders::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
