<?php

namespace backend\controllers;

use backend\models\search\RealOrderSearch;
use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\RealOrder;
use common\models\Shop;
use common\services\ShopService;
use Yii;
use common\base\BaseController;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use common\models\User;

class RealOrderController extends BaseController
{

    /**
     * @routeName Real订单管理
     * @routeDescription Real订单管理
     */
    public function actionIndex()
    {
        $shop = ShopService::getShopMap(Base::PLATFORM_REAL_DE);
        return $this->render('index',['shop'=>$shop]);
    }

    public function model(){
        return new RealOrder();
    }

    /**
     * @routeName Real订单列表
     * @routeDescription Real订单列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;

        $searchModel=new RealOrderSearch();
        $where=$searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);

        $shop_ids = ArrayHelper::getColumn($data['list'],'shop_id');
        $shop = Shop::find()->select(['id','name'])->where(['id'=>$shop_ids])->indexBy('id')->asArray()->all();

        $lists = [];
        foreach ($data['list'] as $v) {
            $info = $v;
            $user = User::getInfo($v['admin_id']);
            $info['shop_name'] = empty($shop[$v['shop_id']])?'':$shop[$v['shop_id']]['name'];
            $info['admin_name'] = empty($user['nickname']) ? '' : $user['nickname'];
            $info['real_order_status_desc'] = empty(RealOrder::$real_order_status_map[$info['real_order_status']]) ? '' : RealOrder::$real_order_status_map[$info['real_order_status']];
            $info['real_delivery_status_desc'] = empty(RealOrder::$real_delivery_status_map[$info['real_delivery_status']]) ? '' : RealOrder::$real_delivery_status_map[$info['real_delivery_status']];
            $info['amazon_status_desc'] = empty(RealOrder::$amazon_status_map[$info['amazon_status']]) ? '' : RealOrder::$amazon_status_map[$info['amazon_status']];
            $info['date'] = empty($info['date'])?'':date('Y-m-d',$info['date']);
            $info['amazon_arrival_time'] = empty($info['amazon_arrival_time'])?'':date('Y-m-d',$info['amazon_arrival_time']);
            $lists[] = $info;
        }

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS,'获取成功',
            $lists,$data['pages']->totalCount
        );
    }

    /**
     * @routeName 新增Real订单
     * @routeDescription 创建新的Real订单
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $model = new RealOrder();
        if ($req->isPost) {
            $data = $req->post();
            $data = $this->dataDeal($data);
            $data['admin_id'] = Yii::$app->user->identity->id;
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($model->load($data, '') && $model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        }
        $model->buyer_phone = '0000';
        $model->country = 'Deutschland';
        $shop = ShopService::getShopMap(Base::PLATFORM_REAL_DE);
        return $this->render('update',['model' => $model,'shop'=>$shop]);
    }

    /**
     *
     * @param $data
     * @return mixed
     */
    private function dataDeal($data){
        $data['date'] = empty($data['date'])?0:strtotime($data['date']);
        $data['amazon_arrival_time'] = empty($data['amazon_arrival_time'])?0:strtotime($data['amazon_arrival_time']);
        $data['status'] = !empty($data['status']) && $data['status']==1?1:0;
        $data['real_order_amount'] = $data['count'] * $data['real_price'];
        $data['profit'] = $data['real_order_amount'] * 0.875 - $data['amazon_price'] * $data['count'];
        $data['amazon_buy_url'] = 'https://www.amazon.de/dp/' . $data['asin'];
        return $data;
    }

    /**
     * @routeName 更新Real订单
     * @routeDescription 更新Real订单信息
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
            $data = $req->post();
            $data = $this->dataDeal($data);
            if ($model->load($data, '') == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }

            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        } else {
            $model['date'] = empty($model['date'])?'':date('Y-m-d',$model['date']);
            $model['amazon_arrival_time'] = empty($model['amazon_arrival_time'])?'':date('Y-m-d',$model['amazon_arrival_time']);
            $shop = ShopService::getShopMap(Base::PLATFORM_REAL_DE);
            return $this->render('update', ['model' => $model,'shop'=>$shop]);
        }
    }

    /**
     * @routeName 删除Real订单
     * @routeDescription 删除指定Real订单
     * @return array
     * @throws
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $model = $this->findModel($id);
        $model->delete_time = time();
        if ($model->save()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除失败", []);
        }
    }

    /**
     * @param $id
     * @return null|RealOrder
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = RealOrder::findOne($id)) !== null) {
           return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * @routeName Real订单导出
     * @routeDescription Real订单导出
     * @return array |Response|string
     */
    public function actionExport()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $searchModel=new RealOrderSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $list = RealOrder::getAllByCond($where);
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
        foreach ($list as $k => $v) {
            $data[$k]['date'] = empty($v['date'])?'':date('Y-m-d',$v['date']);
            $data[$k]['order_id'] = $v['order_id'];
            $data[$k]['goods_name'] = $v['goods_name'];
            $data[$k]['asin'] = $v['asin'];
            $data[$k]['count'] = $v['count'];
            $data[$k]['amazon_buy_url'] = $v['amazon_buy_url'];
            $data[$k]['specification'] = $v['specification'];
            $data[$k]['amazon_price'] = $v['amazon_price'];
            $data[$k]['real_price'] = $v['real_price'];
            $data[$k]['real_order_amount'] = $v['real_order_amount'];
            $data[$k]['profit'] = $v['profit'];
            $data[$k]['buyer_name'] = $v['buyer_name'];
            $data[$k]['buyer_phone'] = $v['buyer_phone'];
            $data[$k]['address'] = $v['address'];
            $data[$k]['city'] = $v['city'];
            $data[$k]['area'] = $v['area'];
            $data[$k]['postcode'] = $v['postcode'];
            $data[$k]['country'] = $v['country'];
            $data[$k]['real_track_no'] = $v['real_track_no'];
            $data[$k]['real_delivery_status_desc'] = empty(RealOrder::$real_delivery_status_map[$v['real_delivery_status']]) ? '' : RealOrder::$real_delivery_status_map[$v['real_delivery_status']];
            $data[$k]['real_order_status_desc'] = empty(RealOrder::$real_order_status_map[$v['real_order_status']]) ? '' : RealOrder::$real_order_status_map[$v['real_order_status']];
            $data[$k]['amazon_order_id'] = $v['amazon_order_id'];
            $data[$k]['amazon_arrival_time'] = empty($v['amazon_arrival_time'])?'':date('Y-m-d',$v['amazon_arrival_time']);
            $data[$k]['amazon_status_desc'] = empty(RealOrder::$amazon_status_map[$v['amazon_status']]) ? '' : RealOrder::$amazon_status_map[$v['amazon_status']];
            $data[$k]['swipe_buyer_id'] = $v['swipe_buyer_id'];
            $data[$k]['logistics_id'] = $v['logistics_id'];

        }

        $column = [
            'date' => '日期',
            'order_id' => '订单号',
            'goods_name' => '产品名称',
            'asin' => '产品ASIN',
            'count' => '购买数量',
            'amazon_buy_url' => '亚马逊买货链接',
            'specification' => '规格型号',
            'amazon_price' => '亚马逊售价',
            'real_price' => 'Real售价',
            'real_order_amount' => 'Real订单金额',
            'profit' => '利润',
            'buyer_name' => '买家名称',
            'buyer_phone' => '电话',
            'address' => '地址',
            'city' => '城市',
            'area' => '区',
            'postcode' => '邮编',
            'country' => '国家',
            'real_track_no' => 'Real跟踪号',
            'real_delivery_status_desc' => 'Real发货状态',
            'real_order_status_desc' => '亚马逊预计到货时间',
            'amazon_order_id' => '亚马逊订单号',
            'amazon_arrival_time' => '亚马逊预计到货时间',
            'amazon_status_desc' => '亚马逊状态',
            'swipe_buyer_id' => '刷单买家号机器编号',
            'logistics_id' => '亚马逊物流订单号',
        ];

        return [
            'key' => array_keys($column),
            'header' => array_values($column),
            'list' => $data,
            'fileName' => 'real订单导出' . date('ymdhis')
        ];
    }

    /**
     * @routeName 下载发票
     * @routeDescription 下载Real订单发票
     * @throws
     */
    public function actionInvoice($id)
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $model = RealOrder::find()->where(['id'=>$id])->asArray()->one();
        $shop = Shop::findOne(['id'=>$model['shop_id']]);
        $pdf = new \TCPDF('P', 'mm',  'S12R', true, 'UTF-8', false);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetFont('helvetica', '', 9);

        $pdf->AddPage();

        //发票号：R+月份+年份+-8位随机数字（如R1120-12345678）
        $invoice_no = 'R' . date('Ym', $model['date']) . '-' . CommonUtil::randString(8, 1);

        $model['invoice_no'] = $invoice_no;


        $price3 = $model['real_price'] * $model['count'];//售价
        $price1 = number_format($price3 / 1.16, 2);//税前价格 售价除以1.16
        $price2 = number_format($price1 * 0.16, 2);//增值税金额 售价乘以0.16

        $goods = [
            ['goods_name' => $model['goods_name'], 'goods_num' => $model['count'], 'price1' => $price1, 'price2' => $price2, 'price3' => $price3],
        ];

        $html = $this->render('/real-order/invoice/'.$shop['invoice_template'],[
            'model' => $model,
            'goods' => $goods
        ]);

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Rechnungen'.$model['order_id'].'.pdf', 'D');
    }

}