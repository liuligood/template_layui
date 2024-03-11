<?php

namespace backend\controllers;

use backend\models\search\OrderSettlementSearch;
use common\base\BaseController;
use common\components\statics\Base;
use common\models\BaseAR;
use common\models\FinancialPlatformSalesPeriod;
use common\models\Shop;
use common\services\ShopService;
use Yii;
use common\models\OrderSettlement;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;


class OrderSettlementController extends BaseController
{

    public function model(){
        return new OrderSettlement();
    }

    /**
     * @routeName 订单待结算
     * @routeDescription 订单待结算
     */
    public function actionIndex()
    {
        $tag = 1;
        $searchModel=new OrderSettlementSearch();
        $searchModel->search(Yii::$app->request->queryParams,$tag);
        return $this->render('index',['tag'=>$tag,'searchModel' => $searchModel]);
    }

    /**
     * @routeName 订单已结算
     * @routeDescription 订单已结算
     */
    public function actionSettledIndex()
    {
        $tag = 2;
        $searchModel=new OrderSettlementSearch();
        $searchModel->search(Yii::$app->request->queryParams,$tag);
        return $this->render('index',['tag'=>$tag,'searchModel' => $searchModel]);
    }

    /**
     * @routeName 订单未结算
     * @routeDescription 订单未结算
     */
    public function actionUnconfirmedIndex()
    {
        $tag = 0;
        $searchModel=new OrderSettlementSearch();
        $searchModel->search(Yii::$app->request->queryParams,$tag);
        return $this->render('index',['tag'=>$tag,'searchModel' => $searchModel]);
    }


    /**
     * @routeName 订单结算列表
     * @routeDescription 订单结算列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $tag = $req->get('tag',0);
        $searchModel=new OrderSettlementSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams,$tag);
        $order_statistics = [];
        if(!empty($searchModel->platform_type) || !empty($searchModel->shop_id)) {
            $order_statistics = OrderSettlement::dealWhere($where,'currency,sum(sales_amount) as sales_amount,sum(commission_amount) as commission_amount,sum(refund_amount) as refund_amount,sum(other_amount) as other_amount,sum(cancellation_amount) as cancellation_amount,sum(refund_commission_amount) as refund_commission_amount,sum(platform_type_freight) as platform_type_freight,sum(freight) as freight,sum(procurement_amount) as procurement_amount,sum(total_amount) as total_amount')->groupBy('currency')->asArray()->all();
        }
        $data = $this->lists($where);

        $sales_period_ids = [];
        foreach ($data['list'] as $v) {
            $sales_period_ids = array_merge($sales_period_ids,explode(',',$v['sales_period_ids']));
            $sales_period_ids = array_unique($sales_period_ids);
        }
        $financial_platform_sales_period = FinancialPlatformSalesPeriod::find()->where(['id'=>$sales_period_ids])->indexBy('id')->asArray()->all();

        $lists = array_map(function ($info) use ($financial_platform_sales_period){
            $info['order_time'] = Yii::$app->formatter->asDatetime($info['order_time'],'php:Y-m-d H:i');
            $info['delivery_time'] = Yii::$app->formatter->asDatetime($info['delivery_time'],'php:Y-m-d H:i');
            $info['settlement_time'] = Yii::$app->formatter->asDatetime($info['settlement_time'],'php:Y-m-d');
            $info['collection_time'] = Yii::$app->formatter->asDatetime($info['collection_time'],'php:Y-m-d');
            $model = ShopService::getShopMap();
            $info['shop_id'] = $model[$info['shop_id']];
            $info['platform_type'] = Base::$platform_maps[$info['platform_type']];
            $sales_period_ids = explode(',',$info['sales_period_ids']);
            $sales_period_name = [];
            foreach ($sales_period_ids as $v) {
                if(empty($v) || empty($financial_platform_sales_period[$v])) {
                    continue;
                }
                $financial_platform_sales_period_v = $financial_platform_sales_period[$v];
                $sales_period_name[] = date('Y-m-d',$financial_platform_sales_period_v['data']) .'~' . date('Y-m-d',$financial_platform_sales_period_v['stop_data']) .'('.$financial_platform_sales_period_v['id'].')';
            }
            $info['sales_period_name'] = implode('<br/>',$sales_period_name);
            return $info;
        }, $data['list']);

        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$lists,$data['pages']->totalCount,$order_statistics);
    }

    /**
     * @routeName 订单待结算统计列表
     * @routeDescription 订单待结算统计列表
     */
    public function actionStatisticsIndex()
    {
        return $this->statisticsList(1);
    }

    /**
     * @routeName 订单未结算统计列表
     * @routeDescription 订单待结算统计列表
     */
    public function actionStatisticsUnconfirmedIndex()
    {
        return $this->statisticsList(0);
    }

    /**
     * @routeName 订单已结算统计列表
     * @routeDescription 订单已结算统计列表
     */
    public function actionStatisticsSettledIndex()
    {
        return $this->statisticsList(2);
    }

    public function statisticsList($tag)
    {
        $req = Yii::$app->request;
        //$tag = $req->get('tag');
        $searchModel=new OrderSettlementSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams,$tag);

        $page = Yii::$app->request->get('page');
        if(empty($page)) {
            $page = Yii::$app->request->post('page',1);
        }
        $pageSize = Yii::$app->request->get('limit');
        if(empty($pageSize)) {
            $pageSize = Yii::$app->request->post('limit',20);
        }

        $is_shop = false;
        if(!empty($searchModel->platform_type) || !empty($searchModel->shop_id)) {
            $is_shop = true;
        }
        $group_by_field = $is_shop?'shop_id':'platform_type';
        $select = $group_by_field.' as source,currency,count(*) as order_cut,count(case when refund_amount != 0 then 1 else null end) as refund_order_cut,sum(sales_amount) as sales_amount,sum(commission_amount) as commission_amount,sum(refund_amount) as refund_amount,sum(other_amount) as other_amount,sum(cancellation_amount) as cancellation_amount,sum(refund_commission_amount) as refund_commission_amount,sum(platform_type_freight) as platform_type_freight,sum(freight) as freight,sum(procurement_amount) as procurement_amount,sum(total_amount) as total_amount,sum(total_profit) as total_profit';
        $query = OrderSettlement::find()->groupBy('currency,'.$group_by_field);
        $list = OrderSettlement::getAllByCond($where,null, $select, $query);

        if($is_shop) {
            $shop_ids = ArrayHelper::getColumn($list,'source');
            $shop_lists = Shop::find()->where(['id'=>$shop_ids])->select(['id','name'])->indexBy('id')->asArray()->all();
            if($tag == 1) {
                $financial_platform_sales_period = FinancialPlatformSalesPeriod::find()->where(['shop_id' => $shop_ids])
                    ->select('max(id) as id,max(collection_time) as collection_time,shop_id')->groupBy('shop_id')->indexBy('shop_id')->all();
                $financial_platform_sales_period_ids = ArrayHelper::getColumn($financial_platform_sales_period, 'id');
                $financial_platform_sales_period_ids_all = FinancialPlatformSalesPeriod::find()->where(['id' => $financial_platform_sales_period_ids])
                    ->select('id,data,stop_data')->indexBy('id')->asArray()->all();
            }
        }

        $lists = [];
        foreach ($list as $v) {
            $lists[$v['source']]['type'] = $group_by_field;
            if(empty($lists[$v['source']]['source_name'])) {
                if ($is_shop) {
                    $lists[$v['source']]['source_name'] = $shop_lists[$v['source']]['name'];
                    if ($tag == 1) {
                        $financial_platform_sales_period_info = empty($financial_platform_sales_period[$v['source']]) ? '' : $financial_platform_sales_period[$v['source']];
                        $lists[$v['source']]['last_collection_time'] = empty($financial_platform_sales_period_info['collection_time'])?'无':date('Y-m-d',$financial_platform_sales_period_info['collection_time']);
                        if (isset($financial_platform_sales_period_info['id'])) {
                            $financial_platform_sales_period_ids_all_info = empty($financial_platform_sales_period_ids_all[$financial_platform_sales_period_info['id']]) ? '' : $financial_platform_sales_period_ids_all[$financial_platform_sales_period_info['id']];
                        }
                        $lists[$v['source']]['last_financial'] = (empty($financial_platform_sales_period_ids_all_info['data'])?'':date('Y-m-d',$financial_platform_sales_period_ids_all_info['data'])).' ~ '.(empty($financial_platform_sales_period_ids_all_info['stop_data'])?'':date('Y-m-d',$financial_platform_sales_period_ids_all_info['stop_data']));
                    }
                } else {
                    $lists[$v['source']]['source_name'] = Base::$platform_maps[$v['source']];
                }
            }

            if (empty($lists[$v['source']]['order_cut'])) {
                $lists[$v['source']]['order_cut'] = 0;
            }
            $lists[$v['source']]['order_cut'] += $v['order_cut'];

            if (empty($lists[$v['source']]['refund_order_cut'])) {
                $lists[$v['source']]['refund_order_cut'] = 0;
            }
            $lists[$v['source']]['refund_order_cut'] += $v['refund_order_cut'];

            if (empty($lists[$v['source']]['procurement_amount'])) {
                $lists[$v['source']]['procurement_amount'] = 0;
            }
            $lists[$v['source']]['procurement_amount'] += $v['procurement_amount'];

            if (empty($lists[$v['source']]['freight'])) {
                $lists[$v['source']]['freight'] = 0;
            }
            $lists[$v['source']]['freight'] += $v['freight'];

            if (empty($lists[$v['source']]['total_profit'])) {
                $lists[$v['source']]['total_profit'] = 0;
            }
            $lists[$v['source']]['total_profit'] += $v['total_profit'];

            $lists[$v['source']]['currency'][] = $v;
        }

        //$pages = new Pagination(['totalCount' => 20, 'pageSize' => $pageSize]);

        return $this->render('statistics', [
            'searchModel' => $searchModel,
            'list' => $lists,
            'is_shop' => $is_shop,
            //'pages' => $pages,
            'tag'=>$tag,
        ]);
    }

    protected function findModel($id)
    {
        if (($model = OrderSettlement::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
