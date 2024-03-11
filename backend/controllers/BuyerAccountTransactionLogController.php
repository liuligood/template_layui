<?php

namespace backend\controllers;

use backend\models\BuyerAccountTransactionLogAdd;
use backend\models\search\BuyerAccountTransactionLogSearch;
use common\models\BuyerAccount;
use common\models\BuyerAccountTransactionLog;
use common\models\BuyGoods;
use common\models\Order;
use common\services\buyer_account\BuyerAccountTransactionService;
use common\services\ImportResultService;
use moonland\phpexcel\Excel;
use Yii;
use common\base\BaseController;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class BuyerAccountTransactionLogController extends BaseController
{
    /**
     * 获取model
     * @return \common\models\BaseAR
     */
    public function model(){
        return new BuyerAccountTransactionLog();
    }

    /**
     * @routeName 买家账号交易流水管理
     * @routeDescription 买家账号交易流水管理
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $buyer_id = $req->get('buyer_id');
        return $this->render('index',['buyer_id'=>$buyer_id]);
    }

    /**
     * @routeName 买家账号交易流水列表
     * @routeDescription 买家账号交易流水列表
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $searchModel = new BuyerAccountTransactionLogSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);

        $data = $this->lists($where);

        $lists = array_map(function ($info) {
            $buyer = BuyerAccount::findOne(['buyer_id'=>$info['buyer_id']]);
            $info['relation_no'] = '';
            $info['buy_relation_no'] = '';
            if($info['type'] == BuyerAccountTransactionService::TYPE_ORDER || $info['type'] == BuyerAccountTransactionService::TYPE_REFUND) {
                $buy_goods = BuyGoods::findOne(['id' => $info['type_id']]);
                $order = Order::findOne(['order_id'=>$buy_goods['order_id']]);
                $info['relation_no'] = empty($order)?'':$order['relation_no'];
                $info['buy_relation_no'] = empty($buy_goods)?'':$buy_goods['buy_relation_no'];
            }
            $info['ext_no'] = $buyer['ext_no'];
            $info['amazon_account'] = $buyer['amazon_account'];
            $info['type_desc'] = BuyerAccountTransactionService::$type_map[$info['type']];
            $info['transaction_type_desc'] = BuyerAccountTransactionService::$transaction_type_map[$info['transaction_type']];
            $info['now_money'] = $info['money'] + $info['org_money'];
            $info['add_time_desc'] = empty($info['add_time'])?'':date('Y-m-d H:i:s',$info['add_time']);
            return $info;
        }, $data['list']);

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $data['pages']->totalCount
        );
    }

    /**
     * @routeName 充值
     * @routeDescription 充值
     * @throws
     */
    public function actionRecharge()
    {
        $req = Yii::$app->request;
        $model = new BuyerAccountTransactionLogAdd();
        if ($req->isPost) {
            $data = $req->post();
            Yii::$app->response->format = Response::FORMAT_JSON;
            if (empty($data['transaction_type'])) {
                return $this->FormatArray(self::REQUEST_FAIL, '交易类型不能为空', []);
            }
            if (empty($data['ext_no'])) {
                return $this->FormatArray(self::REQUEST_FAIL, '买家分机号', []);
            }
            if (empty($data['money'])) {
                return $this->FormatArray(self::REQUEST_FAIL, '充值金额不能为空', []);
            }
            if ($data['money'] <0) {
                return $this->FormatArray(self::REQUEST_FAIL, '充值金额不能小于0', []);
            }
            try {
                $result = BuyerAccountTransactionService::recharge($data['transaction_type'], $data['ext_no'], $data['money'], $data['desc']);
                if ($result) {
                    return $this->FormatArray(self::REQUEST_SUCCESS, "充值成功", []);
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, '充值失败', []);
                }
            } catch (\Exception $e) {
                return $this->FormatArray(self::REQUEST_FAIL, '充值失败:' . $e->getMessage(), []);
            }
        }
        $model->ext_no = $req->get('ext_no');
        return $this->render('recharge',['model' => $model]);
    }

    /**
     * @routeName 后台变更金额
     * @routeDescription 后台变更金额
     * @throws
     */
    public function actionAdmin()
    {
        $req = Yii::$app->request;
        $model = new BuyerAccountTransactionLogAdd();
        if ($req->isPost) {
            $data = $req->post();
            Yii::$app->response->format = Response::FORMAT_JSON;
            if (empty($data['ext_no'])) {
                return $this->FormatArray(self::REQUEST_FAIL, '买家分机号', []);
            }
            if (empty($data['money'])) {
                return $this->FormatArray(self::REQUEST_FAIL, '变更金额不能为空', []);
            }
            try {
                $result = BuyerAccountTransactionService::admin($data['ext_no'], $data['money'], '',$data['desc']);
                if ($result) {
                    return $this->FormatArray(self::REQUEST_SUCCESS, "变更成功", []);
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, '变更失败', []);
                }
            } catch (\Exception $e) {
                return $this->FormatArray(self::REQUEST_FAIL, '变更失败:' . $e->getMessage(), []);
            }
        }
        $model->ext_no = $req->get('ext_no');
        return $this->render('admin',['model' => $model]);
    }


    /**
     * @routeName 订单支付
     * @routeDescription 订单支付
     * @throws
     */
    public function actionOrder()
    {
        $req = Yii::$app->request;
        $model = new BuyerAccountTransactionLogAdd();
        if ($req->isPost) {
            $data = $req->post();
            Yii::$app->response->format = Response::FORMAT_JSON;
            if (empty($data['ext_no'])) {
                return $this->FormatArray(self::REQUEST_FAIL, '买家分机号不能为空', []);
            }
            if (empty($data['money'])) {
                return $this->FormatArray(self::REQUEST_FAIL, '充值金额不能为空', []);
            }
            if (empty($data['type_id'])) {
                return $this->FormatArray(self::REQUEST_FAIL, '销售单号不能为空', []);
            }
            try {
                $result = BuyerAccountTransactionService::order($data['ext_no'], $data['money'], $data['type_id'], $data['desc']);
                if ($result) {
                    return $this->FormatArray(self::REQUEST_SUCCESS, "支付成功", []);
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, '支付失败', []);
                }
            } catch (\Exception $e) {
                return $this->FormatArray(self::REQUEST_FAIL, '支付失败:' . $e->getMessage(), []);
            }
        }
        $model->ext_no = $req->get('ext_no');
        return $this->render('order',['model' => $model]);
    }

    /**
     * @param $id
     * @return null|BuyerAccountTransactionLog
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = BuyerAccountTransactionLog::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * @routeName 买家账号交易流水导出
     * @routeDescription 买家账号交易流水导出
     * @return array |Response|string
     */
    public function actionExport()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $searchModel=new BuyerAccountTransactionLogSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $list = BuyerAccountTransactionLog::getAllByCond($where);
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
            $buyer = BuyerAccount::findOne(['buyer_id'=>$v['buyer_id']]);
            $type_id = $v['type_id'];
            if($v['type'] == BuyerAccountTransactionService::TYPE_ORDER) {
                $buy_goods = BuyGoods::findOne(['id' => $v['type_id']]);
                $order = Order::findOne(['order_id'=>$buy_goods['order_id']]);
                $data[$k]['relation_no'] = empty($order)?'':$order['relation_no'];
                $data[$k]['buy_relation_no'] = empty($buy_goods)?'':$buy_goods['buy_relation_no'];
            }
            $data[$k]['id'] = $v['id'];
            $data[$k]['ext_no'] = $buyer['ext_no'];
            $data[$k]['amazon_account'] = $buyer['amazon_account'];
            $data[$k]['transaction_type_desc'] = BuyerAccountTransactionService::$transaction_type_map[$v['transaction_type']];
            $data[$k]['type_desc'] = BuyerAccountTransactionService::$type_map[$v['type']];
            $data[$k]['type_id'] = $type_id;
            $data[$k]['org_money'] = $v['org_money'];
            $data[$k]['money'] = $v['money'];
            $data[$k]['now_money'] = $v['money'] + $v['org_money'];
            $data[$k]['desc'] = $v['desc'];
            $data[$k]['add_time_desc'] = empty($v['add_time'])?'':date('Y-m-d',$v['add_time']);
        }

        $column = [
            'id' => 'id',
            'ext_no' => '买家分机号',
            'amazon_account' => '亚马逊邮箱',
            'transaction_type_desc' => '交易方式',
            'type_desc'=>'类型',
            'relation_no' => '关联销售单号',
            'buy_relation_no' => '关联亚马逊订单号',
            'org_money' => '原金额',
            'money' => '变动金额',
            'now_money' => '变动后金额',
            'desc' => '描述',
            'add_time_desc' => '变动时间',
        ];

        return [
            'key' => array_keys($column),
            'header' => array_values($column),
            'list' => $data,
            'fileName' => '买家账号交易流水导出' . date('ymdhis')
        ];
    }


    /**
     * @routeName 买家账号充值导入
     * @routeDescription 买家账号充值导入
     * @return array
     * @throws
     */
    public function actionRechargeImport()
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
            'ext_no' => '分机号',
            'transaction_type' => '充值类型',
            'money' => '充值金额',
            'desc' => '备注',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }
        if(empty($keyMap['ext_no']) || empty($keyMap['transaction_type']) || empty($keyMap['money'])) {
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

            if (empty($ext_no) || empty($transaction_type) || empty($money)) {
                $errors[$i] = '分机号、充值类型、充值金额不能为空';
                continue;
            }

            try {
                $transaction_type = array_search($transaction_type,BuyerAccountTransactionService::$transaction_type_map);
                if(empty($transaction_type)) {
                    $errors[$i] = '不存在该充值类型';
                    continue;
                }

                $result = BuyerAccountTransactionService::recharge($transaction_type, $ext_no, $money, empty($desc)?'':$desc);
                if (!$result) {
                    $errors[$i] = '充值失败';
                    continue;
                }
            }catch (\Exception $e) {
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
                $info['rvalue1'] = $row[$keyMap['ext_no']];
                $info['rvalue2'] = '';
                $info['rvalue3'] = '';
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('买家账号充值', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }

        return $this->FormatArray(self::REQUEST_SUCCESS, "导入成功", []);
    }

}