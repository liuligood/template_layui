<?php

namespace backend\controllers;

use backend\models\search\GoodsStockCheckSearch;
use backend\models\search\GrabGoodsCheckSearch;
use common\models\Goods;
use common\models\GoodsShop;
use common\models\GoodsStockCheckCycle;
use common\models\grab\GrabGoods;
use common\models\GoodsStockCheck;
use common\services\FGrabService;
use Yii;
use common\base\BaseController;
use yii\web\Response;

class GoodsStockCheckController extends BaseController
{

    /**
     * 获取model
     * @return \common\models\BaseAR
     */
    public function model()
    {
        return new GoodsStockCheck();
    }

    /**
     * @routeName 商品库存检测管理
     * @routeDescription 商品库存检测管理
     */
    public function actionIndex()
    {
        $cycle = GoodsStockCheckCycle::find()->select('id,name,add_time')->orderBy('id desc')->limit(3)->asArray()->all();
        $cycle_lists = [];
        foreach ($cycle as $v) {
            $cycle_lists[$v['id']] = $v['name'] . '（' . date('Y-m-d' , $v['add_time']) . '）';
        }
        return $this->render('index', ['cycle_lists' => $cycle_lists]);
    }

    public function query($type = 'select')
    {
        return $this->model()->find()
            ->alias('ms')->select('ms.source,ms.cycle_id,ms.sku_no,ms.old_stock,ms.stock,gs.platform_type,gs.shop_id,ms.add_time,gs.price')
            ->leftJoin(GoodsShop::tableName() . ' gs', 'gs.goods_no= ms.goods_no');
    }

    /**
     * @routeName 商品库存检测列表
     * @routeDescription 商品库存检测列表
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $searchModel = new GoodsStockCheckSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where,'ms.add_time desc');

        $shop_map = \common\services\ShopService::getShopMap();
        $lists = array_map(function ($info) use ($shop_map) {
            return $this->_dealLists($info, $shop_map);
        }, $data['list']);

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $data['pages']->totalCount
        );
    }

    public function _dealLists($info, $shop_map)
    {
        $info['shop_name'] = empty($info['shop_id']) ? '' : $shop_map[$info['shop_id']];
        $info['source_desc'] = empty(FGrabService::$source_map[$info['source']]) ? '' : FGrabService::$source_map[$info['source']]['name'];
        $info['old_stock_desc'] = Goods::$stock_map[$info['old_stock']];
        $info['stock_desc'] = Goods::$stock_map[$info['stock']];
        $info['add_time'] = date('Y-m-d H:i', $info['add_time']);
        return $info;
    }

    /**
     * @routeName 商品库存检测导出
     * @routeDescription 商品库存检测导出
     * @return array |Response|string
     */
    public function actionExport()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $search_model = new GoodsStockCheckSearch();
        $where = $search_model->search(Yii::$app->request->queryParams);
        $model = $this->model();
        $query = $this->query();
        $list = $model::getAllByCond($where, 'ms.add_time desc', [], $query);
        $data = $this->export($list);

        return $this->FormatArray(self::REQUEST_SUCCESS, "", $data);
    }

    /**
     * 导出
     * @param $list
     * @return array
     */
    public function export($list)
    {
        $data = [];
        $shop_map = \common\services\ShopService::getShopMap();
        foreach ($list as $k => $v) {
            $v = $this->_dealLists($v, $shop_map);
            $data[$k]['source_desc'] = $v['source_desc'];
            $data[$k]['shop_name'] = $v['shop_name'];
            $data[$k]['sku_no'] = $v['sku_no'];
            $data[$k]['price'] = $v['price'];
            $data[$k]['old_stock_desc'] = $v['old_stock_desc'];
            $data[$k]['stock_desc'] = $v['stock_desc'];
            $data[$k]['add_time'] = $v['add_time'];
        }

        $column = [
            'source_desc' => '来源平台',
            'shop_name' => '店铺',
            'sku_no' => 'SKU',
            'price' => '价格',
            'old_stock_desc' => '历史库存状态',
            'stock_desc' => '库存状态',
            'add_time' => '时间',
        ];

        return [
            'key' => array_keys($column),
            'header' => array_values($column),
            'list' => $data,
            'fileName' => '商品库存检测导出' . date('ymdhis')
        ];
    }

    /**
     * @routeName 开启新一轮检测
     * @routeDescription 开启新一轮检测
     * @throws
     * @return string |Response |array
     */
    public function actionCreateCycle()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $cycle = GoodsStockCheckCycle::find()->orderBy('id desc')->one();
        $new_cycle = 1;
        if (!empty($cycle)) {
            $name = $cycle['name'];
            $old_cycle = str_replace(['第', '轮'], '', $name);
            $new_cycle = $old_cycle + 1;
        }
        $model = new GoodsStockCheckCycle();
        $model->name = '第' . $new_cycle . '轮';
        $model->status = GoodsStockCheckCycle::STATUS_NONE;
        if ($model->save()) {
            if (!empty($cycle)) {
                $cycle->status = GoodsStockCheckCycle::STATUS_FINISH;
                $cycle->save();
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "开启成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
        }
    }

}