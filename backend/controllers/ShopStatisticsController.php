<?php

namespace backend\controllers;

use common\base\BaseController;
use common\components\statics\Base;
use common\services\ShopService;
use Yii;
use common\models\ShopStatistics;
use backend\models\search\ShopStatisticsSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;


class ShopStatisticsController extends BaseController
{
    public function model()
    {
        return new ShopStatistics();
    }

    /**
     * @routeName 店铺统计主页
     * @routeDescription 店铺统计主页
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $tag = $req->get('tag',1);
        $searchModel = new ShopStatisticsSearch();
        $query_params = Yii::$app->request->queryParams;
        $where = $searchModel->search($query_params,$tag);
        $list = ShopStatistics::find()->where($where)->asArray()->all();
        foreach ($list as &$v){
            $v['platform_type'] = Base::$platform_maps[$v['platform_type']];
            $shop = ShopService::getShopMap();
            $v['shop_name'] = $shop[$v['shop_id']];
        }
        return $this->render('index',[
            'tag' => $tag,
            'list' => $list,
        ]);
    }

}
