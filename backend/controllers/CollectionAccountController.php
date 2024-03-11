<?php

namespace backend\controllers;

use common\base\BaseController;
use common\models\financial\CollectionBankCards;
use common\models\financial\CollectionCurrency;
use common\models\Shop;
use Yii;
use common\models\financial\CollectionAccount;
use backend\models\search\CollectionAccountSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;


class CollectionAccountController extends BaseController
{

    public function model()
    {
        return new CollectionAccount();
    }


    /**
     * @routeName 收款账号主页
     * @routeDescription 收款账号主页
     */
    public function actionIndex()
    {
        return $this->render('index');
    }


    /**
     * @routeName 收款账号列表
     * @routeDescription 收款账号列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel=new CollectionAccountSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);
        foreach ($data['list'] as &$info){
            $info['add_time'] = Yii::$app->formatter->asDatetime($info['add_time']);
            $info['update_time'] = Yii::$app->formatter->asDatetime($info['update_time']);
            $info['collection_platform'] = Shop::$collection_maps[$info['collecton_platform']];
            $banks = CollectionBankCards::find()->where(['collection_account_id'=>$info['id']])->one();
            $info['is_sync'] = true;
            if (empty($banks)){
                $info['is_sync'] = false;
            }
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data['list'],$data['pages']->totalCount);
    }
//    public function query($type = 'select')
//    {
//        $query = CollectionCurrency::find()
//            ->alias('og')->select('g.*,og.currency,og.money');
//        $query->leftJoin(CollectionAccount::tableName() . ' g', 'og.collection_account_id = g.id');
//        return $query;
//    }
    /**
     * @routeName 收款账号以及币种主页
     * @routeDescription 收款账号以及币种主页
     */
    public function actionMindex()
    {
        $req = Yii::$app->request;
        $searchModel=new CollectionAccountSearch();
        $params = $req->queryParams;
        //如能获取到collection_shop
        $where=$searchModel->search($params);
        $data = $this->lists($where);
        $alist=[];
        foreach ($data['list'] as &$list) {
           $arrays = CollectionCurrency::find()->where(['collection_account_id'=>$list['id']])->asArray()->all();
           if (!empty($arrays)){
           foreach ($arrays as $array){
               $item['id'] = $array['id'];
               $item['collection_account'] = $list['collection_account'];
               $item['collection_platform'] = Shop::$collection_maps[$list['collecton_platform']];
               $item['currency'] = $array['currency'];
               $item['money'] = $array['money'];
               $alist[] = $item;
            }}
        }
        return $this->render('mindex', [ 'searchModel' => $searchModel, 'list' => $alist, 'pages' => $data['pages']]);
    }

    /**
     * @routeName 新增收款账号
     * @routeDescription 创建新的收款账号
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new CollectionAccount();
            if ($model->load($req->post(), '') && $model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        }
        return $this->render('create');
    }

    /**
     * @routeName 更新收款账号
     * @routeDescription 更新收款账号信息
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
            return $this->render('update', ['info' => $model->toArray()]);
        }
    }

    /**
     * @routeName 删除收款账号
     * @routeDescription 删除指定收款账号
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
     * Finds the CollectionAccount model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CollectionAccount the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CollectionAccount::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
