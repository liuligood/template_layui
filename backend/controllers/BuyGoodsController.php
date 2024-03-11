<?php

namespace backend\controllers;

use backend\models\search\BuyGoodsSearch;
use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\BuyerAccount;
use common\models\BuyGoods;
use common\models\Order;
use common\models\OrderGoods;
use common\services\buyer_account\BuyerAccountTransactionService;
use common\services\ImportResultService;
use common\services\order\OrderService;
use Exception;
use moonland\phpexcel\Excel;
use Yii;
use common\base\BaseController;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class BuyGoodsController extends BaseController
{

    /**
     * @routeName 购买商品管理
     * @routeDescription 购买商品管理
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $tag = $req->get('tag',10);
        return $this->render('index',['tag'=>$tag]);
    }

    public function model(){
        return new BuyGoods();
    }

    /**
     * @routeName 购买商品列表
     * @routeDescription 购买商品列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $tag = $req->get('tag',10);

        $params = Yii::$app->request->queryParams;
        $searchModel=new BuyGoodsSearch();
        $where=$searchModel->search($params,$tag);
        $sort = 'id DESC';
        //已有货
        if($tag == 11){
            $sort = 'update_time desc';
        }
        $data = $this->lists($where,$sort);

        $shop_map = \common\services\ShopService::getOrderShop();
        $lists = [];
        foreach ($data['list'] as $v) {
            $info = $v;
            $order = OrderService::getOneByOrderId($v['order_id']);

            $order_goods = OrderGoods::find()->where(['id'=>$v['order_goods_id']])->one();

            $info['shop_name'] = empty($order['shop_id']) ? '' : $shop_map[$order['shop_id']];
            $info['goods_price'] = empty($order_goods['goods_income_price']) ? 0 : $order_goods['goods_income_price'];
            $info['order_add_time'] = empty($order['date'])?'':date('Y-m-d',$order['date']);
            $info['delivery_status_desc'] = empty($order['delivery_status']) ? '' : Order::$delivery_status_map[$order['delivery_status']];
            $info['platform_type_desc'] = empty($info['platform_type'])?'':Base::$buy_platform_maps[$info['platform_type']];
            $info['relation_no'] = empty($order['relation_no']) ? '' : $order['relation_no'];
            $info['country'] = empty($order['country']) ? '' : $order['country'];
            $info['city'] = empty($order['city']) ? '' : $order['city'];
            $info['area'] = empty($order['area']) ? '' : $order['area'];
            $info['company_name'] = empty($order['company_name']) ? '' : $order['company_name'];
            $info['buyer_name'] = empty($order['buyer_name']) ? '' : $order['buyer_name'];
            $info['buyer_phone'] = empty($order['buyer_phone']) ? '' : $order['buyer_phone'];
            $info['postcode'] = empty($order['postcode']) ? '' : $order['postcode'];
            $info['address'] = empty($order['address']) ? '' : $order['address'];
            $info['logistics_channels_id_desc'] =  empty($info['logistics_channels_id']) ? '' : Order::$logistics_channels_map[$info['logistics_channels_id']];
            $info['buy_goods_status_desc'] = empty(BuyGoods::$buy_goods_status_map[$info['buy_goods_status']]) ? '' : BuyGoods::$buy_goods_status_map[$info['buy_goods_status']];
            $info['after_sale_status_desc'] = empty(BuyGoods::$after_sale_status_map[$info['after_sale_status']]) ? '' : BuyGoods::$after_sale_status_map[$info['after_sale_status']];

            $info['arrival_time'] = empty($info['arrival_time'])?'':date('Y-m-d',$info['arrival_time']);
            $info['update_time'] = empty($info['update_time'])?'':date('Y-m-d H:i:s',$info['update_time']);
            $info['add_time'] = empty($info['add_time'])?'':date('Y-m-d H:i:s',$info['add_time']);
            $lists[] = $info;
        }

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS,'获取成功',
            $lists,$data['pages']->totalCount
        );
    }

    /**
     *
     * @param $data
     * @return mixed
     */
    private function dataDeal($data,$buy_goods_model){
        if ($buy_goods_model['buy_goods_status'] != BuyGoods::BUY_GOODS_STATUS_DELIVERY) {
            $data['arrival_time'] = empty($data['arrival_time']) ? 0 : strtotime($data['arrival_time']);
        }
        if(empty($buy_goods_model)) {
            return $data;
        }

        //购买状态
        if($buy_goods_model['buy_goods_status'] == BuyGoods::BUY_GOODS_STATUS_BUY) {
            $buy_goods_model['buy_goods_status'] = BuyGoods::BUY_GOODS_STATUS_DELIVERY;
        }else if($buy_goods_model['buy_goods_status'] == BuyGoods::BUY_GOODS_STATUS_DELIVERY) {  //发货状态
            $buy_goods_model['buy_goods_status'] = BuyGoods::BUY_GOODS_STATUS_FINISH;
        }
        return $data;
    }

    /**
     * @routeName 更新购买商品
     * @routeDescription 更新购买商品信息
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
        $old_model = clone $model;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $data = $req->post();
            $data = $this->dataDeal($data,null);
            if ($model->load($data, '') == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }

            if ($model->save()) {
                $order = OrderService::getOneByOrderId($model['order_id']);
                if(!empty($model['track_no'])) {
                    $order->arrival_time = $model['arrival_time'];
                    $order->logistics_channels_id = $model['logistics_channels_id'];
                    $order->track_no = $model['track_no'];
                    $order->save();
                }

                //更新亚马逊价格
                if($old_model['buy_goods_price'] != $model['buy_goods_price']) {
                    $order_goods = OrderGoods::findOne(['id'=>$old_model['order_goods_id']]);
                    if($model['buy_goods_price'] != $order_goods['goods_cost_price']) {
                        $order_goods['goods_cost_price'] = $model['buy_goods_price'];
                        $order_goods->save();
                        OrderService::updateOrderPrice($model['order_id']);
                    }
                }
                if($model['buy_goods_status'] == BuyGoods::BUY_GOODS_STATUS_BUY && !empty($model['swipe_buyer_id'])) {
                    try {
                        $price = $model['buy_goods_price']*$model['buy_goods_num'];
                        BuyerAccountTransactionService::order($model['swipe_buyer_id'], $price, $model['id']);
                    } catch (Exception $e) {
                        CommonUtil::logs('订单消费错误：' . $model['id'] . ' 分机号:' . $model['swipe_buyer_id'] . ' 金额：' . $model['buy_goods_price'] . ' ' . $e->getMessage(), 'buyer_account_transaction_error');
                    }
                }

                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        } else {
            $order = OrderService::getOneByOrderId($model['order_id']);
            $model['arrival_time'] = empty($model['arrival_time'])?'':date('Y-m-d',$model['arrival_time']);
            return $this->render('update', ['model' => $model,'order'=>$order]);
        }
    }

    /**
     * @routeName 状态变更
     * @routeDescription 状态变更
     * @throws
     */
    public function actionChangeStatus()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            $id = $req->post('id');
        }
        $model = $this->findModel($id);
        $old_model = clone $model;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $data = $req->post();
            $data = $this->dataDeal($data,$model);
            $is_buy = false;
            if((!empty($data['buy_goods_status']) && $data['buy_goods_status'] == BuyGoods::BUY_GOODS_STATUS_BUY)) {
                $is_buy = true;
                //检测账号
                $buyer_account = BuyerAccount::find()->where(['ext_no'=>$data['swipe_buyer_id']])->one();
                if(empty($buyer_account)){
                    return $this->FormatArray(self::REQUEST_FAIL, "买家账号不存在", []);
                }
                $price = $data['buy_goods_price']*$model['buy_goods_num'];
                if($buyer_account['amount'] < $price){
                    return $this->FormatArray(self::REQUEST_FAIL, "买家账号金额不足", []);
                }
            }
            if ($model->load($data, '') == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }

            if ($model->save()) {
                if($is_buy && !empty($model['swipe_buyer_id'])) {
                    try {
                        $price = $model['buy_goods_price']*$model['buy_goods_num'];
                        BuyerAccountTransactionService::order($model['swipe_buyer_id'], $price, $model['id']);
                    } catch (Exception $e) {
                        CommonUtil::logs('订单消费错误：' . $model['id'] . ' 分机号:' . $model['swipe_buyer_id'] . ' 金额：' . $price . ' ' . $e->getMessage(), 'buyer_account_transaction_error');
                    }
                }

                if($model->buy_goods_status == BuyGoods::BUY_GOODS_STATUS_FINISH){
                    $order = OrderService::getOneByOrderId($model['order_id']);
                    if($order['order_status'] == Order::ORDER_STATUS_WAIT_PURCHASE) {
                        $order->arrival_time = $model['arrival_time'];
                        $order->logistics_channels_id = $model['logistics_channels_id'];
                        $order->track_no = $model['track_no'];
                        $order->save();
                    }
                }
                OrderService::updateOrderStatus($model['order_id']);

                //更新亚马逊价格
                if($old_model['buy_goods_price'] != $model['buy_goods_price']) {
                    $order_goods = OrderGoods::findOne(['id'=>$old_model['order_goods_id']]);
                    if($model['buy_goods_price'] != $order_goods['goods_cost_price']) {
                        $order_goods['goods_cost_price'] = $model['buy_goods_price'];
                        $order_goods->save();
                        OrderService::updateOrderPrice($model['order_id']);
                    }
                }
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        } else {
            $order = OrderService::getOneByOrderId($model['order_id']);
            $model['arrival_time'] = empty($model['arrival_time'])?'':date('Y-m-d',$model['arrival_time']);
            return $this->render('change_status', ['model' => $model,'order'=>$order]);
        }
    }

    /**
     * @routeName 售后处理
     * @routeDescription 售后处理
     * @throws
     */
    public function actionAfterSale()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            $id = $req->post('id');
        }
        $model = $this->findModel($id);
        $old_model = clone $model;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $data = $req->post();
            $data['after_sale_time'] = empty($data['after_sale_time']) ? 0 : strtotime($data['after_sale_time']);
            if ($model->load($data, '') == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }

            if ($model->save()) {
                //退款
                if($old_model->after_sale_status != $model->after_sale_status && $model->after_sale_status == BuyGoods::AFTER_SALE_STATUS_REFUND) {
                    if(!empty($model['swipe_buyer_id']) && !in_array($model['buy_goods_status'],[BuyGoods::BUY_GOODS_STATUS_NONE,BuyGoods::BUY_GOODS_STATUS_OUT_STOCK,BuyGoods::BUY_GOODS_STATUS_ERROR_CON])) {
                        try {
                            $price = $model['buy_goods_price']*$model['buy_goods_num'];
                            BuyerAccountTransactionService::refund($model['swipe_buyer_id'], $price, $model['id']);
                        } catch (Exception $e) {
                            CommonUtil::logs('订单退款错误：' . $model['id'] . ' 分机号:' . $model['swipe_buyer_id'] . ' 金额：' . $price . ' ' . $e->getMessage(), 'buyer_account_transaction_error');
                        }
                    }
                }
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        } else {
            $order = OrderService::getOneByOrderId($model['order_id']);
            $model['arrival_time'] = empty($model['arrival_time'])?'':date('Y-m-d',$model['arrival_time']);
            $model['after_sale_time'] = empty($model['after_sale_time'])?'':date('Y-m-d',$model['after_sale_time']);
            return $this->render('after_sale', ['model' => $model,'order'=>$order]);
        }
    }

    /**
     * @routeName 批量发货
     * @routeDescription 批量发货
     * @return array |Response|string
     */
    public function actionBatchShip()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->post('id');
        $buy_goods = BuyGoods::find()->where(['id' => $id])->all();
        foreach ($buy_goods as $v) {
            if (!in_array($v['buy_goods_status'], [BuyGoods::BUY_GOODS_STATUS_BUY])) {
                continue;
            }

            if(empty($v['arrival_time'])){
                $v['arrival_time'] =  strtotime("+30 day",strtotime(date('Y-m-d')));
            }
            $v['logistics_id'] = $v['buy_relation_no'];
            $v['buy_goods_status'] = BuyGoods::BUY_GOODS_STATUS_DELIVERY;
            $v->save();
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "批量发货成功", []);
        //return $this->FormatArray(self::REQUEST_FAIL, "批量发货失败", []);
    }

    /**
     * @routeName 导入物流单号
     * @routeDescription 导入物流单号
     * @return array
     * @throws
     */
    public function actionImportLogistics()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $file = UploadedFile::getInstanceByName('file');
        if (!in_array($file->extension, ['xlsx', 'xls'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "只允许使用以下文件扩展名的文件：xlsx, xls。", []);
        }

        // 读取excel文件
        $data = Excel::import($file->tempName, [
            'setFirstRecordAsKeys' => false,
        ]);

        // 多Sheet
        if (isset($data[0])) {
            $data = $data[0];
        }

        $rowKeyTitles = [
            'relation_no' => '销售单号',
            'track_no' => '物流订单号',
            'logistics_channels_id' => '物流渠道',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }
        if(empty($keyMap['relation_no']) || empty($keyMap['track_no'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }

        $count = count($data);
        $success = 0;
        $errors = [];
        for ($i = 2; $i <= $count; $i++) {
            $row = $data[$i];
            foreach ($row as &$rowValue) {
                $rowValue = !empty($rowValue) ? str_replace(' ', '', $rowValue) : '';
            }

            foreach (array_keys($rowKeyTitles) as $rowMapKey) {
                $rowKey = isset($keyMap[$rowMapKey]) ? $keyMap[$rowMapKey] : '';
                $$rowMapKey = isset($row[$rowKey]) ? $row[$rowKey] : '';
            }

            if (empty($relation_no) || empty($track_no)) {
                $errors[$i] = '销售单号或物流订单号不能为空';
                continue;
            }

            try {
                $this->ship($relation_no, $track_no, empty($logistics_channels_id) ? 1 : $logistics_channels_id);
            }catch (Exception $e) {
                $errors[$i] = $e->getMessage();
                continue;
            }

            $success++;
        }

        if(!empty($errors)) {
            $lists = [];
            foreach ($errors as $i => $error) {
                $row = $data[$i];
                $info = [];
                $info['index'] = $i;
                $info['rvalue1'] = $row[$keyMap['relation_no']];
                $info['rvalue2'] = $row[$keyMap['track_no']];
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('物流单号', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }

        return $this->FormatArray(self::REQUEST_SUCCESS, "导入成功", []);
    }


    /**
     * @routeName 购买导入
     * @routeDescription 购买导入
     * @return array
     * @throws
     */
    public function actionImportBuy()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $file = UploadedFile::getInstanceByName('file');
        if (!in_array($file->extension, ['xlsx', 'xls'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "只允许使用以下文件扩展名的文件：xlsx, xls。", []);
        }

        // 读取excel文件
        $data = Excel::import($file->tempName, [
            'setFirstRecordAsKeys' => false,
        ]);

        // 多Sheet
        if (isset($data[0])) {
            $data = $data[0];
        }

        $rowKeyTitles = [
            'relation_no' => '销售单号',
            'buy_relation_no' => '亚马逊订单号',
            'swipe_buyer_id' => '刷单买家号机器编号',
            'buy_goods_price' => '商品价格',
            'arrival_time' => '预计到货时间',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }
        if(empty($keyMap['relation_no']) || empty($keyMap['buy_relation_no']) || empty($keyMap['swipe_buyer_id'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }

        $count = count($data);
        $success = 0;
        $errors = [];
        for ($i = 2; $i <= $count; $i++) {
            $row = $data[$i];
            foreach ($row as &$rowValue) {
                $rowValue = !empty($rowValue) ? str_replace(' ', '', $rowValue) : '';
            }

            foreach (array_keys($rowKeyTitles) as $rowMapKey) {
                $rowKey = isset($keyMap[$rowMapKey]) ? $keyMap[$rowMapKey] : '';
                $$rowMapKey = isset($row[$rowKey]) ? $row[$rowKey] : '';
            }

            if (empty($relation_no) || empty($buy_relation_no) || empty($swipe_buyer_id)) {
                $errors[$i] = '销售单号、亚马逊订单号、刷单买家号机器编号不能为空';
                continue;
            }

            try {
                //$arrival_time = '';
                if(!empty($arrival_time)){
                    $arrival_time = trim($arrival_time);
                    $day_arr = explode('.',$arrival_time);
                    if(count($day_arr) != 2){
                        $errors[$i] = '预计到货时间格式有误';
                        continue;
                    }
                    $day_str = date('Y').'-'.$day_arr[0].'-'.$day_arr[1];
                    $arrival_time = strtotime($day_str);
                }
                $this->buy($relation_no, $buy_relation_no, $swipe_buyer_id,$buy_goods_price,$arrival_time);
            }catch (Exception $e) {
                $errors[$i] = $e->getMessage();
                continue;
            }

            $success++;
        }

        if(!empty($errors)) {
            $lists = [];
            foreach ($errors as $i => $error) {
                $row = $data[$i];
                $info = [];
                $info['index'] = $i;
                $info['rvalue1'] = $row[$keyMap['relation_no']];
                $info['rvalue2'] = $row[$keyMap['buy_relation_no']];
                $info['rvalue3'] = $row[$keyMap['swipe_buyer_id']];
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('亚马逊购买', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }

        return $this->FormatArray(self::REQUEST_SUCCESS, "导入成功", []);
    }

    /**
     * 购买订单号
     * @param $relation_no
     * @param $buy_relation_no
     * @param $swipe_buyer_id
     * @param $buy_goods_price
     * @param $arrival_time
     */
    public function buy($relation_no,$buy_relation_no,$swipe_buyer_id,$buy_goods_price = '',$arrival_time = '')
    {
        $order_lists = Order::find()->where(['relation_no' => $relation_no])->all();
        if (empty($order_lists)) {
            throw new Exception('找不到该订单数据');
        }
        if (count($order_lists) > 1) {
            throw new Exception('存在多个订单');
        }

        $order = current($order_lists);
        $order_id = $order['order_id'];
        $buy_goods_lists = BuyGoods::find()->where(['order_id' => $order_id])->all();
        if (empty($buy_goods_lists)) {
            throw new Exception('找不到该亚马逊订单数据');
        }
        if (count($buy_goods_lists) > 1) {
            throw new Exception('存在多个亚马逊订单');
        }
        $buy_goods = current($buy_goods_lists);
        $old_buy_goods = clone $buy_goods;

        $buyer_account = BuyerAccount::find()->where(['ext_no'=>$swipe_buyer_id])->one();
        if(empty($buyer_account)){
            throw new Exception('买家账号不存在');
        }
        $price = $buy_goods['buy_goods_price'] * $buy_goods['buy_goods_num'];
        if($buy_goods_price != '') {
            $price = $buy_goods_price * $buy_goods['buy_goods_num'];
        }
        if($buyer_account['amount'] < $price){
            throw new Exception('买家账号金额不足');
        }

        if (in_array($buy_goods['buy_goods_status'],[BuyGoods::BUY_GOODS_STATUS_BUY,BuyGoods::BUY_GOODS_STATUS_DELIVERY,BuyGoods::BUY_GOODS_STATUS_FINISH,BuyGoods::BUY_GOODS_STATUS_DELETE])) {
            throw new Exception('该亚马逊订单不是未购买状态');
        }

        $buy_goods->buy_relation_no = (string)$buy_relation_no;
        $buy_goods->swipe_buyer_id = (string)$swipe_buyer_id;
        $buy_goods->buy_goods_status = BuyGoods::BUY_GOODS_STATUS_BUY;
        if($buy_goods_price != ''){
            $buy_goods->buy_goods_price = $buy_goods_price;
        }
        if(!empty($arrival_time)){
            $buy_goods->arrival_time = $arrival_time;
        }
        $buy_goods->save();

        if(!empty($arrival_time)){
            $order->arrival_time = $arrival_time;
            $order->save();
        }

        //更新亚马逊价格
        if($old_buy_goods['buy_goods_price'] != $buy_goods['buy_goods_price']) {
            $order_goods = OrderGoods::findOne(['id'=>$old_buy_goods['order_goods_id']]);
            if($buy_goods['buy_goods_price'] != $order_goods['goods_cost_price']) {
                $order_goods['goods_cost_price'] = $buy_goods['buy_goods_price'];
                $order_goods->save();
                OrderService::updateOrderPrice($order_goods['order_id']);
            }
        }

        if(!empty($swipe_buyer_id)) {
            try {
                $price = $buy_goods['buy_goods_price']*$buy_goods['buy_goods_num'];
                BuyerAccountTransactionService::order($swipe_buyer_id,$price, $buy_goods['id']);
            } catch (Exception $e) {
                CommonUtil::logs('订单消费错误：' . $buy_goods['id'] . ' 分机号:' . $swipe_buyer_id . ' 金额：' . $buy_goods['buy_goods_price'] . ' ' . $e->getMessage(), 'buyer_account_transaction_error');
            }
        }
    }

    /**
     * 购买订单号
     * @param $relation_no
     * @param $track_no
     * @param $logistics_channels_id
     */
    public function ship($relation_no,$track_no,$logistics_channels_id)
    {
        $order_lists = Order::find()->where(['relation_no' => $relation_no])->all();
        if (empty($order_lists)) {
            throw new Exception('找不到该订单数据');
        }
        if (count($order_lists) > 1) {
            throw new Exception('存在多个订单');
        }

        $order = current($order_lists);
        $order_id = $order['order_id'];
        $buy_goods_lists = BuyGoods::find()->where(['order_id' => $order_id])->all();
        if (empty($buy_goods_lists)) {
            throw new Exception('找不到该亚马逊订单数据');
        }
        if (count($buy_goods_lists) > 1) {
            throw new Exception('存在多个亚马逊订单');
        }
        $buy_goods = current($buy_goods_lists);

        if (in_array($buy_goods['buy_goods_status'],[BuyGoods::BUY_GOODS_STATUS_FINISH,BuyGoods::BUY_GOODS_STATUS_DELETE])) {
            throw new Exception('该亚马逊订单已完成或已取消');
        }

        $buy_goods->track_no = $track_no;
        if(empty($logistics_channels_id)){
            $logistics_channels_id = 1;
        }
        $buy_goods->logistics_channels_id = $logistics_channels_id;
        $buy_goods->buy_goods_status = BuyGoods::BUY_GOODS_STATUS_FINISH;
        $buy_goods->save();

        if($order['order_status'] == Order::ORDER_STATUS_WAIT_PURCHASE) {
            $order->arrival_time = $buy_goods['arrival_time'];
            $order->logistics_channels_id = $logistics_channels_id;
            $order->track_no = $track_no;
            $order->save();
        }

        OrderService::updateOrderStatus($order_id);
    }

    /**
     * @param $id
     * @return null|BuyGoods
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = BuyGoods::findOne($id)) !== null) {
           return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * @routeName 购买商品导出
     * @routeDescription 购买商品导出
     * @return array |Response|string
     */
    public function actionExport()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $tag = $req->get('tag',1);
        $searchModel=new BuyGoodsSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams,$tag);
        $sort = 'id DESC';
        //已有货
        if($tag == 11){
            $sort = 'update_time desc';
        }
        $list = BuyGoods::getAllByCond($where,$sort);
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
        $shop_map = \common\services\ShopService::getOrderShop();
        foreach ($list as $k => $v) {
            $order = OrderService::getOneByOrderId($v['order_id']);
            $order_goods = OrderGoods::find()->where(['id'=>$v['order_goods_id']])->one();

            $data[$k]['shop_name'] = empty($order['shop_id']) ? '' : $shop_map[$order['shop_id']];
            $data[$k]['order_id'] = $v['order_id'];
            $data[$k]['relation_no'] = $order['relation_no'];
            $data[$k]['asin'] = $v['asin'];
            $data[$k]['ean'] = $order['ean'];
            $data[$k]['buy_goods_num'] = $v['buy_goods_num'];
            $data[$k]['goods_price'] = empty($order_goods['goods_income_price']) ? 0 : $order_goods['goods_income_price'];
            $data[$k]['buy_goods_price'] = $v['buy_goods_price'];
            $data[$k]['buy_goods_url'] = $v['buy_goods_url'];
            $data[$k]['company_name'] = empty($order['company_name']) ? '' : $order['company_name'];
            $data[$k]['buyer_name'] = empty($order['buyer_name']) ? '' : $order['buyer_name'];
            $data[$k]['buyer_phone'] = empty($order['buyer_phone']) ? '' : $order['buyer_phone'];
            $data[$k]['address'] = empty($order['address']) ? '' : $order['address'];
            $data[$k]['city'] = empty($order['city']) ? '' : $order['city'];
            $data[$k]['area'] = empty($order['area']) ? '' : $order['area'];
            $data[$k]['postcode'] = empty($order['postcode']) ? '' : $order['postcode'];
            $data[$k]['country'] = empty($order['country']) ? '' : $order['country'];
            $data[$k]['swipe_buyer_id'] = $v['swipe_buyer_id'];
            $data[$k]['buy_relation_no'] = $v['buy_relation_no'];
            $data[$k]['logistics_id'] = $v['logistics_id'];
            $data[$k]['arrival_time'] =  empty($v['arrival_time'])?'':date('Y-m-d',$v['arrival_time']);
            $data[$k]['logistics_channels_id'] =  empty($v['logistics_channels_id']) ? '' : Order::$logistics_channels_map[$v['logistics_channels_id']];
            $data[$k]['track_no'] = $v['track_no'];
            $data[$k]['buy_goods_status_desc'] = empty(BuyGoods::$buy_goods_status_map[$v['buy_goods_status']]) ? '' : BuyGoods::$buy_goods_status_map[$v['buy_goods_status']];
            $data[$k]['after_sale_status_desc'] = empty(BuyGoods::$after_sale_status_map[$v['after_sale_status']]) ? '' : BuyGoods::$after_sale_status_map[$v['after_sale_status']];
            $data[$k]['update_time'] = empty($v['update_time'])?'':date('Y-m-d H:i:s',$v['update_time']);
        }

        $column = [
            'order_id' => '订单号',
            'relation_no' => '销售单号',
            'shop_name' => '销售店铺',
            'asin' => '商品ASIN',
            'ean' => 'EAN',
            'buy_goods_num' => '购买数量',
            'buy_goods_price'=>'售价',
            'goods_price' => '销售平台价格',
            'buy_goods_url' => '亚马逊买货链接',
            'company_name' => '公司名称',
            'buyer_name' => '买家名称',
            'buyer_phone' => '电话',
            'address' => '地址',
            'city' => '城市',
            'area' => '区',
            'postcode' => '邮编',
            'country' => '国家',
            'swipe_buyer_id' => '刷单买家号机器编号',
            'buy_relation_no' => '亚马逊订单号',
            'logistics_id' => '亚马逊物流订单号',
            'arrival_time' => '预计到货时间',
            'logistics_channels_id' => '物流渠道',
            'track_no' => '物流订单号',
            'buy_goods_status_desc' => '状态',
            'after_sale_status_desc' => '售后状态',
            'update_time' => '更新时间',
        ];

        return [
            'key' => array_keys($column),
            'header' => array_values($column),
            'list' => $data,
            'fileName' => '订单导出' . date('ymdhis')
        ];
    }

}