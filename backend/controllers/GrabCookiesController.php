<?php

namespace backend\controllers;

use common\base\BaseController;
use common\components\statics\Base;
use common\models\grab\GrabCookies;
use Yii;
use backend\models\search\GrabCookiesSearch;
use yii\web\NotFoundHttpException;
use yii\web\Response;


class GrabCookiesController extends BaseController
{
    public function model()
    {
        return new GrabCookies();
    }


    /**
     * @routeName 采集Cookies主页
     * @routeDescription 采集Cookies主页
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * @routeName 采集Cookies列表
     * @routeDescription 采集Cookies列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel=new GrabCookiesSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);
        foreach ($data['list'] as &$info){
            $info['add_time'] = Yii::$app->formatter->asDatetime($info['add_time']);
            $info['update_time'] = Yii::$app->formatter->asDatetime($info['update_time']);
            $info['platform_type'] = empty(Base::$platform_maps[$info['platform_type']]) ? '' : Base::$platform_maps[$info['platform_type']];
            $info['status'] = empty(GrabCookies::$status_maps[$info['status']]) ? '' : GrabCookies::$status_maps[$info['status']];
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data['list'],$data['pages']->totalCount);
    }


    /**
     * @routeName 新增采集Cookie
     * @routeDescription 新增采集Cookie
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new GrabCookies();
            $model->load($req->post(), '');
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, "添加失败", []);
            }
        }
        return $this->render('create');
    }

    /**
     * @routeName 更新采集Cookie
     * @routeDescription 更新采集Cookie
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
                return $this->FormatArray(self::REQUEST_FAIL, "更新失败", []);
            }
        } else {
            return $this->render('update', ['info' => $model->toArray()]);
        }
    }

    /**
     * @routeName 删除采集Cookie
     * @routeDescription 删除采集Cookie
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


    /**
     * Finds the GrabCookies model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return GrabCookies the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = GrabCookies::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
