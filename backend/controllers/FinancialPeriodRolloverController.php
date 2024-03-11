<?php

namespace backend\controllers;

use common\base\BaseController;
use common\components\statics\Base;
use common\models\ExchangeRate;
use common\models\FinancialPlatformSalesPeriod;
use common\models\Shop;
use common\services\financial\PlatformSalesPeriodService;
use common\services\goods\GoodsService;
use common\services\ImportResultService;
use common\services\ShopService;
use moonland\phpexcel\Excel;
use Yii;
use common\models\FinancialPeriodRollover;
use backend\models\search\FinancialPeriodRolloverSearch;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * FinancialPeriodRolloverController implements the CRUD actions for FinancialPeriodRollover model.
 */
class FinancialPeriodRolloverController extends BaseController
{

    public function model(){
        return new FinancialPeriodRollover();
    }
    
    /**
     * @routeName 账期查看流水表主页
     * @routeDescription 账期查看流水表主页
     */
    public function actionMindex()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $shop = ShopService::getShopMap();
        $searchModel = new FinancialPeriodRolloverSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        if (!empty($id)) {
            $where['financial_id'] = $id;
        }
        $data = $this->lists($where);
        $total = [];
        foreach ($data['list'] as &$list) {
            $list['date'] = date('Y-m-d', $list['date']);
            if ($list['data_post'] != 0) {
                $list['data_post'] = date('Y-m-d', $list['data_post']);
            } else {
                $list['data_post'] = '';
            }
            $list['amount'] = $list['amount'] . " " . $list['currency'];
            $list['collection_time'] = empty($list['collection_time'])?'':date('Y-m-d',$list['collection_time']);
            $list['shop_id'] = $shop[$list['shop_id']];
            $list['platform_type'] = GoodsService::$own_platform_type[$list['platform_type']];
        }
            $operation_amount = FinancialPeriodRollover::dealWhere($where)->select('operation,sum(amount) as amount')->groupBy('operation')->asArray()->all();
            $payment_amount_no = FinancialPeriodRollover::dealWhere($where)->andWhere('collection_time = 0')->select('sum(amount) as amount')->asArray()->one();
            $map = [];
            foreach (PlatformSalesPeriodService::$OPREATION_ALL_MAP as $map_k => $map_v) {
                if (empty($map[$map_k])) {
                    $map[$map_k] = 0;
                }
                foreach ($operation_amount as $item) {
                    if (in_array($item['operation'], $map_v)) {
                        $map[$map_k] += $item['amount'];
                    }
                }
            }
            $total['sales_amount'] = $map[PlatformSalesPeriodService::OPERATION_ONE];
            $total['commission_amount'] = $map[PlatformSalesPeriodService::OPERATION_SIX];
            $total['refund_commission_amount'] = $map[PlatformSalesPeriodService::OPERATION_SER];
            $total['refund_amount'] = $map[PlatformSalesPeriodService::OPERATION_FOR];
            $total['order_amount'] = $map[PlatformSalesPeriodService::OPERATION_TWO];
            $total['promotions_amount'] = $map[PlatformSalesPeriodService::OPERATION_TEN];
            $total['freight'] = $map[PlatformSalesPeriodService::OPERATION_EIG];
            $total['advertising_amount'] = $map[PlatformSalesPeriodService::OPERATION_NIN];
            $total['cancellation_amount'] = $map[PlatformSalesPeriodService::OPERATION_FIR];
            $total['premium'] = $map[PlatformSalesPeriodService::OPERATION_ELE];
            $total['goods_services_amount'] = $map[PlatformSalesPeriodService::OPERATION_TRE];
            $total['objection_amount']=$map[PlatformSalesPeriodService::OPERATION_TWE];
            $total['payment_amount'] = $total['sales_amount']
                + $total['commission_amount'] + $total['refund_commission_amount'] +
                $total['refund_amount'] + $total['order_amount'] + $total['promotions_amount']
                + $total['freight'] + $total['advertising_amount'] + $total['cancellation_amount'] +$total['premium'] + $total['goods_services_amount']+$total['objection_amount'];
            $total['payment_amount_no'] = empty($payment_amount_no['amount']) ? 0 : $payment_amount_no['amount'];
        $cun = [];
        $time = '';
        if (!empty($id)){
        $cun = $this->adminArr();
        $fin = FinancialPlatformSalesPeriod::findOne($id);
        $shop_id = $fin['shop_id'];
        $time = date('Y-m-d', $fin['data']) . '-' . date('Y-m-d', $fin['stop_data']);
            if (empty($searchModel->start_date)) {
                $searchModel['start_date'] = date('Y-m-d', $fin['data']);
            }
            if (empty($searchModel->end_date)) {
                $searchModel['end_date'] = date('Y-m-d', $fin['stop_data']);
            }
            if (empty($searchModel->shop_id)){
                $searchModel['shop_id'] = $shop_id;
            }
            if (empty($searchModel->id)){
                $searchModel['id'] = $id;
            }
        }
        return $this->render('mindex', ['cun' => $cun, 'time' => $time, 'searchModel' => $searchModel, 'list' => $data['list'], 'pages' => $data['pages'],'sale_id'=>$id,'total'=>$total]);
    }
    /**
     * @routeName 导入页面
     * @routeDescription 导入页面
     */
    public function actionView()
    {
        $req = Yii::$app->request;
        if($req->get('id')){
            $id = $req->get('id');
        }else{$id = 4;}
        return $this->render('view',['ida'=>$id]);
    }

    /**
     * @routeName 导入区分
     * @routeDescription 导入区分
     */
    public function actionImport()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $file = UploadedFile::getInstanceByName('file');
        if (!in_array($file->extension, ['xlsx', 'xls', 'csv'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "只允许使用以下文件扩展名的文件：xlsx, csv,xls。", []);
        }

        // 读取excel文件
        $data = Excel::import($file->tempName, [
            'setFirstRecordAsKeys' => false,
        ]);
        $req = Yii::$app->request;
        $shop = $req->get('shop');
        $fin_id = $req->get('fin');
        $financial = FinancialPlatformSalesPeriod::findOne($fin_id);
        $stime = $financial->data;
        $ptime = $financial->stop_data;
        $type_id = Shop::find()->where(['id' => $shop])->select('platform_type')->asArray()->one();
        $result = false;
        switch ($type_id['platform_type']) {
            /*case Base::PLATFORM_ALLEGRO:
                $result = $this->allegroImportFinancial($data, $shop, $type_id, $fin_id, $stime, $ptime);
                break;*/
            case Base::PLATFORM_B2W:
                $result = $this->b2WImportFinancial($data, $shop, $type_id, $fin_id, $stime, $ptime);
                break;
            case Base::PLATFORM_OZON:
                $result = $this->ozonImportFinancial($data, $shop, $type_id, $fin_id, $stime, $ptime);
                break;
            case Base::PLATFORM_FRUUGO:
                $result = $this->fruugoImportFinancial($data, $shop, $type_id, $fin_id, $stime, $ptime);
                break;
            case Base::PLATFORM_NOCNOC:
                $result = $this->nocnocImportFinancial($data, $shop, $type_id, $fin_id, $ptime);
                break;
            case Base::PLATFORM_HEPSIGLOBAL:
                $result = $this->hepsiglobalImportFinancial($data, $shop, $type_id, $fin_id, $stime, $ptime);
                break;
            case Base::PLATFORM_FYNDIQ:
                $result = $this->fyndiqImportFinancial($data, $shop, $type_id, $fin_id, $stime, $ptime);
                break;
            case Base::PLATFORM_WALMART:
                $result = $this->fyndiqImportWalmart($data, $shop, $type_id, $fin_id, $ptime);
                break;
            case Base::PLATFORM_MICROSOFT:
                $result = $this->fyndiqImportMicrosoft($data, $shop, $type_id, $fin_id, $ptime);
                break;
        }
        if(is_array($result)) {
            return $result;
        }

        if ($result) {
            $result = (new PlatformSalesPeriodService())->amountStatistics($fin_id);
            if ($result) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "导入成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, "导入失败", []);
            }
        }

        return $this->FormatArray(self::REQUEST_FAIL, "导入失败", []);
    }

    /**
     * @routeName Fyndiq导入账期流水
     * @routeDescription Fyndiq导入账期流水
     * @return array
     * @throws
     */
    public function fyndiqImportFinancial($data,$shop_id,$type_id,$fin_id,$stime,$ptime){
        if (isset($data[0])) {
            $data = $data[0];
        }
        if (empty($data[1]['A']) || empty($data[5]['A'])){
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }
        $number = 5;
        $date_time = $this->dealFyndiq($data[1]);
        while (true) {
            if (stristr($data[$number]['A'],'radbeskrivning')) {
                break;
            }
            $number ++;
        }
        $rowTitles = $this->dealFyndiq($data[$number]);
        $rowKeyTitles = [
            'order id' => 'relation_no',
            'radtyp' => 'operation',
            'radbeskrivning' => 'offer',
            'belopp inkl. Moms' => 'amount',
            'artikelnr' => 'buyer',
            'valuta' => 'currency'
        ];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($k, $rowTitles);
            if ($excelKey === false){
                return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
            }
        }
        $ptime = $ptime+86400;
        $count = count($data);
        $row_title_count = count($rowTitles);
        $success = 0;
        $errors = [];
        for ($i = $number + 1; $i <= $count; $i++) {
            $operation_type = '';
            $row = $data[$i];
            $row_s = implode('x_x',$row);
            if (stristr($row_s,'; ')) {
                $row_s = str_replace(['&#39;','amp;'],'',$row_s);
                $row = explode('; ',html_entity_decode($row_s));
            } else {
                $row = explode('x_x',$row_s);
            }
            $list = [];
            for ($j = 0;$j<$row_title_count;$j++) {
                if (empty($rowKeyTitles[$rowTitles[$j]])){
                    continue;
                }
                $list[$rowKeyTitles[$rowTitles[$j]]] = trim($row[$j]);
            }
            if (empty($list['relation_no']) || empty($list['operation'])) {
                $errors[$i] = '销售单号,操作类型不能为空';
                continue;
            }

            if (empty($list['offer']) || empty($list['amount'])) {
                $errors[$i] = '操作单,金额不能为空';
                continue;
            }
            if (!strstr($list['operation'],'_')){
                $operation_type = PlatformSalesPeriodService::getOperation($list['operation']);
            }
            $amount = json_encode($list['amount']);
            $amount = str_replace(['\u00a0',' ',','],['','','.'],$amount);
            try {
                $time = str_replace([',','/'],['','-'],$date_time[1]);
                $time = strtotime($time);
                if($time < $stime || $time >= $ptime){
                    $errors[$i] = $time.'时间超过规定时间';
                    continue;
                }
                $model = new FinancialPeriodRollover();
                $model['platform_type'] = $type_id['platform_type'];
                $model['shop_id'] = $shop_id;
                $model['financial_id'] = $fin_id;
                $model['date'] = $time;
                $model['amount'] = json_decode($amount);
                $model['relation_no'] = $list['relation_no'];
                $model['currency'] = empty($list['currency']) ? 'SEK' : $list['currency'];
                $model['operation'] = $list['operation'];
                $model['offer'] = $list['offer'];
                $model['buyer'] = $list['buyer'];
                if (!empty($operation_type)){
                    $model['operation'] = $operation_type;
                    $model['offer'] = $list['operation']." ".$list['offer'];
                }
                $model->save();
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
                $row_s = implode('x_x',$row);
                if (stristr($row_s,'; ')) {
                    $row_s = str_replace(['&#39;','amp;'],'',$row_s);
                    $row = explode('; ',html_entity_decode($row_s));
                } else {
                    $row = explode('x_x',$row_s);
                }
                $list = [];
                for ($j = 0;$j<$row_title_count;$j++) {
                    if (empty($rowKeyTitles[$rowTitles[$j]])){
                        continue;
                    }
                    $list[$rowKeyTitles[$rowTitles[$j]]] = trim($row[$j]);
                }
                $info = [];
                $info['index'] = $i;
                $info['rvalue1'] = $list['relation_no'];
                $info['rvalue2'] = $list['operation'];
                $info['rvalue3'] = $list['offer'];
                $info['rvalue4'] = $list['amount'];
                $info['rvalue5'] = $list['buyer'];
                $info['rvalue6'] = $date_time[1];
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('导入账期流水', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }
        return true;
    }
    /**
     * @routeName Walmart导入账期流水
     * @routeDescription Walmart导入账期流水
     * @return array
     * @throws
     */
    public function fyndiqImportWalmart($data,$shop_id,$type_id,$fin_id, $ptime){
        if (isset($data[0])) {
            $data = $data[0];
        }
        $rowKeyTitles = [
            'relation_no' => 'Walmart.com PO #',
            'operation' => 'Transaction Type',
            'amount' => 'Gross Sales Revenue',
            'refund' => 'Refunded Retail Sales',
            'commission' => 'Commission from Sale',
            'offer' => 'Return Reason Description'
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }
        if(empty($keyMap['relation_no']) || empty($keyMap['operation'])) {
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
            if (empty($relation_no) || empty($operation)) {
                $errors[$i] = '销售单号,操作类型不能为空';
                continue;
            }
            try {
                $model = new FinancialPeriodRollover();;
                $model['platform_type'] = $type_id['platform_type'];
                $model['shop_id'] = $shop_id;
                $model['financial_id'] = $fin_id;
                $model['date'] = $ptime;
                if($operation == 'SALE'){
                    $model['amount'] = $amount;
                    $models = new FinancialPeriodRollover();;
                    $models['platform_type'] = $type_id['platform_type'];
                    $models['shop_id'] = $shop_id;
                    $models['financial_id'] = $fin_id;
                    $models['date'] = $ptime;
                    $models['amount'] = -$commission;
                    $models['relation_no'] = $relation_no;
                    $models['currency'] = 'USD';
                    $models['operation'] = 'SALE COMMISSION';
                    $models->save();
                }elseif ($operation == 'REFUNDED'){
                    $model['offer'] = $offer;
                    $model['amount'] = $refund;
                    $models = new FinancialPeriodRollover();;
                    $models['platform_type'] = $type_id['platform_type'];
                    $models['shop_id'] = $shop_id;
                    $models['financial_id'] = $fin_id;
                    $models['date'] = $ptime;
                    $models['amount'] = -$commission;
                    $models['relation_no'] = $relation_no;
                    $models['currency'] = 'USD';
                    $models['operation'] = 'REFUNDED COMMISSION';
                    $models['offer'] = $offer;
                    $models->save();
                }
                $model['relation_no'] = $relation_no;
                $model['currency'] = 'USD';
                $model['operation'] = $operation;
                $model->save();
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
                $info['rvalue1'] = $row[$keyMap['relation_no']];
                $info['rvalue3'] = $row[$keyMap['operation']];
                $info['rvalue5'] = $row[$keyMap['amount']];
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('导入账期流水', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }
        return true;
    }
    /**
     * @routeName Microsoft导入账期流水
     * @routeDescription Microsoft导入账期流水
     * @return array
     * @throws
     */
    public function fyndiqImportMicrosoft($data,$shop_id,$type_id,$fin_id, $ptime){
        if (isset($data[0])) {
            $data = $data[0];
        }
        $rowKeyTitles = [
            'relation_no' => 'Suborder ID',
            'relation_two' => 'Order ID',
            'operation' => 'Transaction Type',
            'amount' => 'Payout Amount',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }
        if(empty($keyMap['operation'])) {
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
            if (empty($operation)) {
                $errors[$i] = '操作类型不能为空';
                continue;
            }
            try {

                $model = new FinancialPeriodRollover();;
                $model['platform_type'] = $type_id['platform_type'];
                $model['shop_id'] = $shop_id;
                $model['financial_id'] = $fin_id;
                $model['date'] = $ptime;
                if(empty($relation_no)){
                    $model['relation_no'] =  $this->dealreal($relation_two);
                }else{$model['relation_no'] = $this->dealreal($relation_no);}
                $model['amount'] = $amount;
                $model['currency'] = 'USD';
                if($operation == 'Payment'){
                    $model['operation'] = 'Sale';
                }else{$model['operation'] = $operation;}
                $model->save();
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
                $info['rvalue1'] = $row[$keyMap['relation_no']];
                $info['rvalue3'] = $row[$keyMap['operation']];
                $info['rvalue5'] = $row[$keyMap['amount']];
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('导入账期流水', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }
        return true;
    }
    /**
     * 处理Microsoft流水订单号
     * @param $fin_id
     * @return bool
     */
    public function dealreal($relation_no){
        $relation_no = str_split($relation_no, 4);
        $relation = implode("-",$relation_no);
        return $relation;
    }


    /**
     * @routeName Hepsiglobal导入账期流水
     * @routeDescription Hepsiglobal导入账期流水
     * @return array
     * @throws
     */
    public function hepsiglobalImportFinancial($data,$shop_id,$type_id,$fin_id,$stime,$ptime){
        if (isset($data[2])) {
            $data = $data[2];
        }
        $rowKeyTitles = [
            'relation_no' => 'Order ID',
            'date' => 'Due Date',
            'operation' => 'Type',
            'offer' => 'Reason',
            'amount' => 'Total $'
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }
        if(empty($keyMap['relation_no']) || empty($keyMap['date']) || empty($keyMap['operation']) || empty($keyMap['offer']) || empty($keyMap['amount'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }

        $ptime = $ptime+86400;
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

            if (empty($relation_no) || empty($date)) {
                $errors[$i] = '销售单号,日期不能为空';
                continue;
            }

            if (empty($operation)) {
                $errors[$i] = '操作类型不能为空';
                continue;
            }


            try {
                $date_time = strtotime($date);
                if($date_time < $stime || $date_time >= $ptime){
                    $errors[$i] = $date.'时间超过规定时间';
                    continue;
                }
                $model = new FinancialPeriodRollover();
                $model['platform_type'] = $type_id['platform_type'];
                $model['shop_id'] = $shop_id;
                $model['financial_id'] = $fin_id;
                $model['date'] = $date_time;
                $model['amount'] = empty($amount) ? 0 : $amount;
                $model['relation_no'] = $relation_no == 1 ? '' : $relation_no;
                $model['currency'] = 'USD';
                $model['operation'] = $operation;
                $model['offer'] = $offer;
                $model->save();
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
                $info['rvalue1'] = $row[$keyMap['relation_no']];
                $info['rvalue2'] = $row[$keyMap['date']];
                $info['rvalue3'] = $row[$keyMap['operation']];
                $info['rvalue4'] = $row[$keyMap['offer']];
                $info['rvalue5'] = $row[$keyMap['amount']];
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('导入账期流水', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }
        return true;
    }




    /**
     * @routeName NOCNOC导入账期流水
     * @routeDescription NOCNOC导入账期流水
     * @return array
     * @throws
     */
    public function nocnocImportFinancial($data,$shop_id,$type_id,$fin_id,$ptime){
        if (isset($data[0])) {
            $data = $data[0];
        }
        $rowKeyTitles = [
            'relation_no' => 'seller_center.order_id',
            'amount' => 'seller_center.amount_usd',
            'offer' => 'seller_center.product_name',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }
        if(empty($keyMap['relation_no']) || empty($keyMap['amount']) || empty($keyMap['offer'])) {
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

            if (empty($relation_no) || empty($amount) || empty($offer)) {
                $errors[$i] = '销售单号,金额不能为空';
                continue;
            }


            try {
                $model = new FinancialPeriodRollover();
                $model['platform_type'] = $type_id['platform_type'];
                $model['shop_id'] = $shop_id;
                $model['financial_id'] = $fin_id;
                $model['date'] = $ptime;
                $model['amount'] = $amount;
                $model['relation_no'] = $relation_no;
                $model['currency'] = 'USD';
                $model['operation'] = 'Sale';
                $model['offer'] = $offer;
                $model->save();
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
                $info['rvalue1'] = $row[$keyMap['relation_no']];
                $info['rvalue2'] = $row[$keyMap['amount']];
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('导入账期流水', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }
        return true;

    }


    /**
     * @routeName Allegro导入账期流水
     * @routeDescription Allegro导入账期流水
     * @return array
     * @throws
     */
    public function allegroImportFinancial($data,$shop_id,$type_id,$fin_id,$stime,$ptime)
    {
        // 多Sheet
        if (isset($data[0])) {
            $data = $data[0];
        }
        $rowKeyTitles = [
            'date' => 'data',
            'data_post' => 'data zaksięgowania',
            'identifier' => 'identyfikator',
            'operation' => 'operacja',
            'buyer' => 'kupujący',
            'offer' => 'oferta',
            'amount' => 'kwota',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }
        if(empty($keyMap['date']) || empty($keyMap['identifier']) || empty($keyMap['operation'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }

        $count = count($data);
        $success = 0;
        $errors = [];
        $ptime = $ptime+86400;
        for ($i = 2; $i <= $count; $i++) {

            $row = $data[$i];
            foreach ($row as &$rowValue) {
                $rowValue = !empty($rowValue) ? str_replace(' ', '', $rowValue) : '';
            }

            foreach (array_keys($rowKeyTitles) as $rowMapKey) {
                $rowKey = isset($keyMap[$rowMapKey]) ? $keyMap[$rowMapKey] : '';
                $$rowMapKey = isset($row[$rowKey]) ? $row[$rowKey] : '';
            }

            if (empty($date) || empty($identifier)) {
                $errors[$i] = '唯一id,结算时间';
                continue;
            }


            try {
                $ar = FinancialPeriodRollover::find()->where(['identifier'=>$identifier])->asArray()->all();
                $counts = count($ar);
                $time = substr($date,0,10);
                $next = substr($date,11,15);
                $time = $this->refindtime($time);
                $date = strtotime($time.$next);

                if($date<$stime||$date>$ptime){
                    $errors[$i] = '时间超过规定时间';
                    continue;
                }
                $time = time();
                $order = new FinancialPeriodRollover();
                $order->date = $date;
                if(!empty($data_post)){
                    $data_post = $this->refindtime($data_post);
                    $data_post = strtotime($data_post);
                }
                $order->data_post =($data_post != 0) ? $data_post : Null;
                $order->identifier = $identifier;
                $order->operation = $operation;
                $order->buyer = $buyer;
                if(!empty($fin_id)){$order->financial_id = $fin_id;}
                $order->offer = $offer;
                $order->shop_id=$shop_id;
                $order->platform_type = $type_id['platform_type'];
                $order->amount = (int)$amount;
                $order->currency = 'Zł';
                $order->add_time = $time;
                $order->update_time = $time;
                if($counts == 0){$order->save();}
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
                $info['rvalue1'] = $row[$keyMap['date']];
                $info['rvalue2'] = $row[$keyMap['identifier']];
                $info['rvalue3'] = $row[$keyMap['data_post']];
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('导入账期流水', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }
        return true;
    }
    /**
     * @routeName OZON导入账期流水
     * @routeDescription OZON导入账期流水
     * @return array
     * @throws
     */
    protected function ozonImportFinancial($data,$shop_id,$type_id,$fin_id,$stime,$ptime)
    {
        // 多Sheet
        if (isset($data[0])) {
            $data = $data[0];
        }
        $rowKeyTitles = [
            'date' => 'Дата начисления',
            'data_post' => 'Дата принятия заказа в обработку или оказания услуги',
            'identifier' => 'Номер отправления или идентификатор услуги',
            'operation' => 'Тип начисления',
            'buyer' => 'Название товара или услуги',
            'offer' => 'Тип начисления',
            'amount' => 'Итого',
            //'currency' => 'Склад отгрузки',
            'commission' => 'За продажу или возврат до вычета комиссий и услуг',
            'refunds' => 'Комиссия за продажу',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        $ptime = $ptime+86400;
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }
        if(empty($keyMap['date']) || empty($keyMap['identifier']) || empty($keyMap['operation'])) {
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

            if (empty($date)) {
                $errors[$i] = '结算时间';
                continue;
            }


            try {
                $currency = 'RUB';
                $time = substr($date,0,10);
                $next = substr($date,11,15);
                $time = $this->refindtime($time);
                $date = strtotime($time.$next);

                if($date<$stime||$date>$ptime){
                    return $this->FormatArray(self::REQUEST_FAIL, "时间超过规定时间", []);
                }
                $time = time();
                $order = new FinancialPeriodRollover();
                $order->date = $date;
                if(!empty($data_post)){
                    $data_post = $this->refindtime($data_post);
                    $data_post = strtotime($data_post);
                }
                if(!empty($fin_id)){$order->financial_id = $fin_id;}
                $order->data_post =($data_post != 0) ? $data_post : Null;
                $order->relation_no = $identifier;
                $order->operation = $operation;
                $order->buyer = ($buyer != null) ? $buyer : '';;
                $order->offer = $offer;
                $order->shop_id=$shop_id;
                $order->platform_type = $type_id['platform_type'];
                if(in_array($operation,['Доставкапокупателю','Возвратперечислениязадоставкупокупателю'])) {
                    if ($operation == 'Доставкапокупателю') {
                        $orders = new FinancialPeriodRollover();
                        $orders->date = $date;
                        $orders->data_post = ($data_post != 0) ? $data_post : Null;
                        $orders->relation_no = $identifier;
                        $orders->operation = 'commission';
                        $orders->buyer = ($buyer != null) ? $buyer : '';;
                        $orders->offer = $offer;
                        $orders->shop_id = $shop_id;
                        if (!empty($fin_id)) {
                            $orders->financial_id = $fin_id;
                        }
                        $orders->currency = $currency;
                        $orders->add_time = $time;
                        $orders->update_time = $time;
                        $orders->platform_type = $type_id['platform_type'];
                        $orders->amount = $refunds;
                        $orders->save();
                        $order->amount = $commission;
                    } elseif ($operation == 'Возвратперечислениязадоставкупокупателю') {
                        $orders = new FinancialPeriodRollover();
                        $orders->date = $date;
                        $orders->data_post = ($data_post != 0) ? $data_post : Null;
                        $orders->relation_no = $identifier;
                        $orders->operation = 'Refundscommissions';
                        $orders->buyer = ($buyer != null) ? $buyer : '';;
                        $orders->offer = $offer;
                        $orders->shop_id = $shop_id;
                        if (!empty($fin_id)) {
                            $orders->financial_id = $fin_id;
                        }
                        $orders->currency = $currency;
                        $orders->add_time = $time;
                        $orders->update_time = $time;
                        $orders->platform_type = $type_id['platform_type'];
                        $orders->amount = $refunds;
                        $orders->save();
                        $order->amount = $commission;
                    }
                    if($commission + $refunds != $amount) {
                        $orders = new FinancialPeriodRollover();
                        $orders->date = $date;
                        $orders->data_post = ($data_post != 0) ? $data_post : Null;
                        $orders->relation_no = $identifier;
                        $orders->operation = 'Other';
                        $orders->buyer = ($buyer != null) ? $buyer : '';;
                        $orders->offer = $offer;
                        $orders->shop_id = $shop_id;
                        if (!empty($fin_id)) {
                            $orders->financial_id = $fin_id;
                        }
                        $orders->currency = $currency;
                        $orders->add_time = $time;
                        $orders->update_time = $time;
                        $orders->platform_type = $type_id['platform_type'];
                        $orders->amount = $amount - $commission - $refunds;
                        $orders->save();
                    }
                }else{$order->amount = $amount;}
                $order->currency = $currency;
                $order->add_time = $time;
                $order->update_time = $time;
                $order->save();
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
                $info['rvalue1'] = $row[$keyMap['date']];
                $info['rvalue2'] = $row[$keyMap['identifier']];
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('导入账期流水', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }
        return true;
    }
    /**
     * @routeName B2W导入账期流水
     * @routeDescription B2w导入账期流水
     * @return array
     * @throws
     */
    public function b2WImportFinancial($data,$shop_id,$type_id,$fin_id,$stime,$ptime)
    {
        // 多Sheet
        if (isset($data[0])) {
            $data = $data[0];
        }
        $rowKeyTitles = [
            'date' => 'Expected Date Payment',
            'data_post' => 'Chargeback Date',
            'operation' => 'Type',
            'identifier' => 'Delivery',
            'offer' => 'Entry',
            'amount' => 'Amount',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }
        if(empty($keyMap['date']) || empty($keyMap['operation'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }
        $count = count($data);
        $success = 0;
        $errors = [];
        $ptime = $ptime+86400;
        for ($i = 2; $i <= $count; $i++) {
            $row = $data[$i];
            foreach (array_keys($rowKeyTitles) as $rowMapKey) {
                $rowKey = isset($keyMap[$rowMapKey]) ? $keyMap[$rowMapKey] : '';
                $$rowMapKey = isset($row[$rowKey]) ? $row[$rowKey] : '';
            }

            if (empty($date) ) {
                $errors[$i] = '结算时间';
                continue;
            }
            try {
                $date = $this->refindtimetwo($date);
                $date = strtotime($date);
                if($date<$stime || $date>=$ptime){
                    $errors[$i] = $date.'时间超过规定时间';
                    continue;
                }
                $time = time();
                $order = new FinancialPeriodRollover();
                $order->date = $date;
                if(!empty($data_post)){
                    $data_post = $this->refindtimetwo($data_post);
                    $data_post = strtotime($data_post);
                }
                $order->data_post =($data_post != 0) ? $data_post : Null;
                $order->operation = $operation;
                $order->relation_no = (string)$identifier;
                if(!empty($fin_id)){$order->financial_id = $fin_id;}
                $order->offer = $offer;
                $order->shop_id=$shop_id;
                $order->shop_id=$shop_id;
                $order->platform_type = $type_id['platform_type'];
                $amount = explode(",",$amount);
                $amount = $amount[0].'.'.$amount[1];
                $order->amount = (float)$amount;
                $order->currency = 'USD';
                $order->add_time = $time;
                $order->update_time = $time;
                $order->save();
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
                $info['rvalue1'] = $row[$keyMap['date']];
                $info['rvalue2'] = $row[$keyMap['identifier']];
                $info['rvalue3'] = $row[$keyMap['data_post']];
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('导入账期流水', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }
        return true;
    }
    /**
     * @routeName Fruugo导入账期流水
     * @routeDescription Fruugo导入账期流水
     * @return array
     * @throws
     */
    public function fruugoImportFinancial($data,$shop_id,$type_id,$fin_id,$stime,$ptime)
    {
        // 多Sheet
        if (isset($data[0])) {
            $data = $data[0];
        }
        $rowKeyTitles = [
            'retailer_price' => 'Retailer Price',
            'transaction_type' => 'Transaction type',
            'relation_no' => 'Retailer order',
            'commission' => 'Commission',
            'funds_processing' => 'Funds Processing fee',
            'ddp_shipment_refund' => 'DDP shipment refund',
            'cancellation_reason' => 'Cancellation reason',
        ];
        $number = 6;
        while (true) {
            $rowTitles = str_ireplace("\n",'',$data[$number]);
            $rowTitles = str_ireplace("  ",' ',$rowTitles);
            $string = implode('',$rowTitles);
            if (stristr($string,'Transaction type')) {
                break;
            }
            $number++;
        }
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            foreach ($rowTitles as $excelKey => $excelValue) {
                if (stristr($excelValue,$v)) {
                    $keyMap[$k] = $excelKey;
                }
            }
        }
        $count = count($data);
        $success = 0;
        $errors = [];
        $ptime = $ptime + 86400;
        $date = $this->refindtimetwo($data[1]["B"]);
        $date = strtotime($date);
        $currency = $data[4]["B"];
        if(empty($currency) || empty($date)) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }
        for ($i = $number + 1; $i <= $count - 2; $i++) {
            try {
                if($date<$stime || $date>=$ptime){
                    $errors[$i] = $date.'时间超过规定时间';
                    continue;
                }
                $row = $data[$i];
                foreach (array_keys($rowKeyTitles) as $rowMapKey) {
                    $rowKey = isset($keyMap[$rowMapKey]) ? $keyMap[$rowMapKey] : '';
                    $$rowMapKey = isset($row[$rowKey]) ? $row[$rowKey] : '';
                }
                $models = [
                    'relation_no' => $relation_no,
                    'offer' => $cancellation_reason,
                    'date' => $date,
                    'platform_type' => $type_id['platform_type'],
                    'currency' => $currency,
                    'financial_id' => $fin_id,
                    'shop_id' => $shop_id
                ];
                PlatformSalesPeriodService::addFinancialPeriodRollover($models,$transaction_type,$retailer_price);
                if($commission > 0){
                    PlatformSalesPeriodService::addFinancialPeriodRollover($models,'commission',-$commission);
                }elseif ($commission < 0){
                    PlatformSalesPeriodService::addFinancialPeriodRollover($models,'Refundscommissions',-$commission);
                }
                if($funds_processing > 0 || $funds_processing < 0){
                    PlatformSalesPeriodService::addFinancialPeriodRollover($models,'premium',-$funds_processing);
                }
                if($ddp_shipment_refund > 0 || $ddp_shipment_refund < 0){
                    PlatformSalesPeriodService::addFinancialPeriodRollover($models,'orders',$ddp_shipment_refund);
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
                $info['rvalue1'] = $date;
                $info['rvalue2'] = $data[$i]['B'];
                $info['rvalue3'] = $data[$i]['E'];
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('导入账期流水', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }
        return true;
    }


    /**
     * @routeName 添加流水
     * @routeDescription 添加流水
     * @return array
     * @throws
     */
    public function actionCreateRollover()
    {
        $req = Yii::$app->request;
        $shop_id = $req->get('shop_id');
        $fin_id = $req->get('id');
        $fin_sales = FinancialPlatformSalesPeriod::find()->where(['id' => $fin_id])->asArray()->one();
        $stop_data = date('Y-m-d',$fin_sales['stop_data'] - 86400);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new FinancialPeriodRollover();

            $date = $req->post('date');
            $platform_type = Shop::find()->where(['id' => $req->post('shop_id')])->select('platform_type')->scalar();
            $fin = FinancialPlatformSalesPeriod::find()->where(['id' => $req->post('financial_id')])->asArray()->one();
            $operation = $req->post('operation');
            $operation = empty(PlatformSalesPeriodService::$OPREATION_ALL_MAP[$operation]) ? '' : PlatformSalesPeriodService::$OPREATION_ALL_MAP[$operation][0];
            if (empty($operation)) {
                return $this->FormatArray(self::REQUEST_FAIL, "操作类型有误", []);
            }
            if (empty($date)){
                return $this->FormatArray(self::REQUEST_FAIL, "出账时间不能为空", []);
            }
            $date = strtotime($date);
            if ($date < $fin['data'] || $date > $fin['stop_data']) {
                return $this->FormatArray(self::REQUEST_FAIL, "出账时间不能超出范围", []);
            }

            $model->load($req->post(), '');
            $model->is_manual = FinancialPeriodRollover::MANUAL;
            $model->operation = $operation;
            $model->platform_type = $platform_type === false ? 0 : $platform_type;
            $model->amount = trim($req->post('amount'));
            $model->date = $date;
            $model->collection_time = empty($req->post('collection_time')) ? 0 : strtotime($req->post('collection_time'));

            if ($model->save()) {
                (new PlatformSalesPeriodService())->amountStatistics($req->post('financial_id'));
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, "添加失败", []);
            }
        }
        return $this->render('create',['shop_id' => $shop_id, 'id' => $fin_id,'currency' => $fin_sales['currency'],'stop_data' => $stop_data]);
    }


    /**
     * @routeName 删除手动流水
     * @routeDescription 删除手动流水
     * @return array
     * @throws
     */
    public function actionDeleteManual()
    {
        $req = Yii::$app->request;
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = $req->get('id');
        $model = $this->findModel($id);
        if ($model->delete()) {
            (new PlatformSalesPeriodService())->amountStatistics($model['financial_id']);
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "删除失败", []);
        }
    }

    protected function refindtime($time){
        $date = explode(".",$time);
        $times = $date[2]."-".$date[1]."-".$date[0];
        return $times;
    }
    protected function refindtimetwo($time){
        $date = explode("/",$time);
        $times = $date[2]."-".$date[1]."-".$date[0];
        return $times;
    }
    /*
     * 修复金额
     */
    protected function repairAmount($amount) {
        return str_ireplace(',','',$amount);
    }
    /**
     * Finds the FinancialPeriodRollover model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return FinancialPeriodRollover the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = FinancialPeriodRollover::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * @routeName 平台销售账期表的数据重改
     * @routeDescription 平台销售账期表的数据重改
     */
    public function actionRestart()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->post('id');
        if ((new PlatformSalesPeriodService())->amountStatistics($id)) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "更新失败", []);
        }
    }

    /**
     * 处理瑞典
     * @param $data
     */
    public function dealFyndiq($data)
    {
        $data = implode('; ',$data);
        $data = str_replace(';  ','; ',$data);
        $data = explode('; ',$data);
        return $data;
    }

    /**
     * @routeName 封装货币
     * @routeDescription 封装货币
     */
    public function adminArr(){
        $admin_lists = ExchangeRate::find()->where('id')->select(['id','currency_code','currency_name'])->asArray()->all();
        $admins = [];
        foreach ($admin_lists as $admin_v){
            $admins[$admin_v['currency_code']] = $admin_v['currency_name'].'('.$admin_v['currency_code'].')';
        }
        return $admins;
    }

}
