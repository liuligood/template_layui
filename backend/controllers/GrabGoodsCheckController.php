<?php

namespace backend\controllers;

use backend\models\search\GrabGoodsCheckSearch;
use common\models\grab\GrabGoods;
use common\models\grab\GrabGoodsCheck;
use common\services\FGrabService;
use Yii;
use common\base\BaseController;
use yii\web\Response;

class GrabGoodsCheckController extends BaseController
{
    /**
     * 获取model
     * @return \common\models\BaseAR
     */
    public function model(){
        return new GrabGoodsCheck();
    }

    /**
     * @routeName 亚马逊商品检测管理
     * @routeDescription 亚马逊商品检测管理
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * @routeName 亚马逊商品检测列表
     * @routeDescription 亚马逊商品检测列表
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $searchModel = new GrabGoodsCheckSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);

        $lists = array_map(function ($info) {
            $info['source_desc'] = empty(FGrabService::$source_map[$info['source']]) ? '' : FGrabService::$source_map[$info['source']]['name'];

            $old_goods_status = $info['old_goods_status'];
            if($info['old_self_logistics'] == GrabGoods::SELF_LOGISTICS_NO){
                $old_goods_status = GrabGoods::GOODS_STATUS_OUT_STOCK;
            }
            $info['old_goods_status_desc'] = GrabGoods::$goods_status_map[$old_goods_status];

            $goods_status = $info['goods_status'];
            if($info['self_logistics'] == GrabGoods::SELF_LOGISTICS_NO){
                $goods_status = GrabGoods::GOODS_STATUS_OUT_STOCK;
            }
            $info['goods_status_desc'] = GrabGoods::$goods_status_map[$goods_status];

            $info['add_time'] = date('Y-m-d H:i',$info['add_time']);
            return $info;
        }, $data['list']);

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $data['pages']->totalCount
        );
    }

    /**
     * @routeName 亚马逊商品检测导出
     * @routeDescription 亚马逊商品检测导出
     * @return array |Response|string
     */
    public function actionExport()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $search_model = new GrabGoodsCheckSearch();
        $where = $search_model->search(Yii::$app->request->queryParams);
        $list = GrabGoodsCheck::getAllByCond($where);
        $data = $search_model->export($list);

        return $this->FormatArray(self::REQUEST_SUCCESS, "", $data);
    }

}