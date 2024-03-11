<?php

namespace backend\controllers;

use common\base\BaseController;
use common\components\statics\Base;
use common\models\financial\CollectionAccount;
use common\models\financial\CollectionBankCards;
use common\models\FinancialPlatformSalesPeriod;
use common\models\goods\GoodsChild;
use common\models\Shop;
use common\services\financial\CollectionCurrencyTransactionService;
use common\services\goods\GoodsService;
use common\services\ImportResultService;
use common\services\order\OrderSettlementService;
use common\services\ShopService;
use moonland\phpexcel\Excel;
use Yii;
use common\models\financial\Collection;
use backend\models\search\CollectionSearch;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\web\UploadedFile;


class CollectionController extends BaseController
{
    public function model()
    {
        return new Collection();
    }

    /**
     * @routeName 回款主页
     * @routeDescription 回款主页
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * @routeName 回款列表
     * @routeDescription 回款列表
     */
    public function actionList(){
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel = new CollectionSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);
        $lists = array_map(function ($info) {
            $info['add_time'] = Yii::$app->formatter->asDatetime($info['add_time']);
            $info['update_time'] = Yii::$app->formatter->asDatetime($info['update_time']);
            $info['collection_date'] = Yii::$app->formatter->asDate($info['collection_date']);
            $info['status'] = Collection::$status_maps[$info['status']];
            $info['platform'] = $info['platform_type'] == 0 ? '' : Base::$platform_maps[$info['platform_type']];
            $account = CollectionAccount::findOne($info['collection_account_id']);
            $info['collection_account'] = $account['collection_account'];
            if ($info['collection_bank_id'] != 0){
                $shop = Shop::find()->where(['collection_bank_cards_id'=>$info['collection_bank_id']])->asArray()->all();
                $shop_name = ArrayHelper::getColumn($shop,'name');
                $bank = CollectionBankCards::findOne($info['collection_bank_id']);
                $info['collection_bank_cards'] = $bank['collection_bank_cards'];
                $info['collection_currency'] = $bank['collection_currency'];
                if ($info['platform_type'] == 0){
                    $info['shop_name'] = empty($shop_name) ? '' : implode(",",$shop_name);
                }else{
                    $shop_names = ShopService::getShopMap($info['platform_type']);
                    $shop_names = array_intersect($shop_names,$shop_name);
                    $info['shop_name'] = empty($shop_names)? '' : implode(",",$shop_names);
                }
            }
            return $info;
        }, $data['list']);
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$lists,$data['pages']->totalCount);
    }


    /**
     * @routeName 新增回款
     * @routeDescription 新增回款
     * @return array
     * @throws
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $collection = CollectionAccount::getRelevancyAccount();
        $bank_cards = CollectionBankCards::getListBank();
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $collection_amount = trim($post['collection_amount']);
            $collection_amount = str_ireplace(',','',$collection_amount);
            $exists = Collection::find()->where(['collection_date' => strtotime($post['collection_date']),'collection_amount' => $collection_amount])->exists();
            if ($exists) {
                return $this->FormatArray(self::REQUEST_FAIL, '该回款日期和金额已存在', []);
            }
            if (!isset($post['collection_bank_id']) || !isset($post['collection_account_id'])) {
                return $this->FormatArray(self::REQUEST_FAIL, '回款账号或回款银行卡不能为空', []);
            }
            $model = new Collection();
            $model['collection_date'] = strtotime($post['collection_date']);
            $model['collection_bank_id'] = $post['collection_bank_id'];
            $model['collection_account_id'] = $post['collection_account_id'];
            $model['collection_amount'] = $collection_amount;
            $model['platform_type'] = empty($post['platform_type']) ? 0 : $post['platform_type'];
            $model['status'] = $post['status'];
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, '添加失败', []);
            }
        }
        return $this->render('create',['collection' => $collection,'bank_cards' => $bank_cards]);
    }


    /**
     * @routeName 导入回款
     * @routeDescription 导入回款
     * @return array
     * @throws
     */
    public function actionImportAmount()
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
            'collection_date' => '回款时间',
            'collection_account' => '回款账号',
            'collection_bank_cards' => '回款银行卡',
            'collection_amount' => '回款金额',
            'platform_type' => '平台',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }

        if(empty($keyMap['collection_date']) || empty($keyMap['collection_account']) || empty($keyMap['collection_bank_cards']) || empty($keyMap['collection_amount']) || empty($keyMap['platform_type'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }

        $count = count($data);
        $success = 0;
        $errors = [];
        for ($i = 2; $i <= $count; $i++) {
            $row = $data[$i];
            foreach ($row as &$rowValue) {
                $rowValue = !empty($rowValue) ? str_replace(' ', ' ', $rowValue) : '';
                $rowValue = !empty($rowValue) ? trim($rowValue) : '';
            }

            foreach (array_keys($rowKeyTitles) as $rowMapKey) {
                $rowKey = isset($keyMap[$rowMapKey]) ? $keyMap[$rowMapKey] : '';
                $$rowMapKey = isset($row[$rowKey]) ? trim($row[$rowKey]) : '';
            }

            if ((empty($collection_date) && empty($collection_bank_cards) && empty($collection_amount))) {
                $errors[$i] = '回款时间,回款银行卡和回款金额不能为空';
                continue;
            }
            try {
                $match = preg_match('/[a-zA-Z]/', $collection_date);
                if ($match == 1){
                    $collection_date = str_ireplace(',','',$collection_date);
                    if (stripos($collection_date,'GMT')) {
                        $collection_date = explode(' ',$collection_date);
                        unset($collection_date[2]);
                    }else{
                        $collection_date = explode(' ',$collection_date);
                        $collection_date[1] = $this->get_month($collection_date[1]);
                    }
                    $collection_date = implode('/',$collection_date);
                }
                $collection_date = str_replace([',','/'],['','-'],$collection_date);
                if(strlen($collection_date) > 16){
                    $collection_date = substr($collection_date , 0 , 16);
                }
                $collection_amount = str_ireplace(',','',$collection_amount);
                $where = ['collection_bank_cards'=>$collection_bank_cards];
                $exists = Collection::find()->where(['collection_date' => strtotime($collection_date),'collection_amount' => $collection_amount])->exists();
                if ($exists) {
                    $errors[$i] = '该回款日期和金额已存在';
                    continue;
                }
                if(!empty($collection_account)) {
                    $account = CollectionAccount::find()->where(['collection_account' => $collection_account])->select('id')->asArray()->one();
                    $where['collection_account_id'] = $account['id'];
                }
                $bank_cards = CollectionBankCards::find()->where($where)->asArray()->all();
                if(empty($bank_cards)) {
                    $errors[$i] = '回款银行卡不存在';
                    continue;
                }
                if(count($bank_cards) > 1) {
                    $errors[$i] = '回款银行卡存在多个';
                    continue;
                }
                $bank_cards = current($bank_cards);
                $model = new Collection();
                $model['collection_date'] = strtotime($collection_date);
                $model['collection_account_id'] = $bank_cards['collection_account_id'];
                $model['collection_amount'] = (float)$collection_amount;
                $model['collection_bank_id'] = $bank_cards['id'];
                $model['status'] = Collection::STATUS_NOT_PROCESSED;
                if (!empty($platform_type)){
                    $platform = array_search($platform_type,Base::$platform_maps);
                    if ($platform === false){
                        $platform = $this->getPlatform($platform_type);
                    }
                    if (empty($platform)){
                        $errors[$i] = '平台名称不存在';
                        continue;
                    }
                    $model['platform_type'] = $platform;
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
                $info = [];
                $info['index'] = $i;
                $info['rvalue1'] = empty($row[$keyMap['collection_date']])?'':$row[$keyMap['collection_date']];
                $info['rvalue2'] = empty($row[$keyMap['collection_account']])?'':$row[$keyMap['collection_account']];
                $info['rvalue3'] = empty($row[$keyMap['collection_bank_cards']])?'':$row[$keyMap['collection_bank_cards']];
                $info['rvalue4'] = empty($row[$keyMap['collection_amount']])?'':$row[$keyMap['collection_amount']];
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('回款', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "导入成功", []);
    }


    /**
     * @routeName 店铺回款
     * @routeDescription 店铺回款
     * @return array
     * @throws
     */
    public function actionCollectionStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $sale_id = (int)$req->get('sale_id');
        $collection_id = (int)$req->get('collection_id');
        $collection = Collection::findOne($collection_id);
        $sale = FinancialPlatformSalesPeriod::findOne($sale_id);
        $sale['payment_back'] = $sale['payment_back'] == 1 ? 2 : 1;
        $sale['collection_time'] = $sale['payment_back'] == 2 ? 0 : $collection['collection_date'];
        if ((new OrderSettlementService())->collectionStatus($sale_id, $sale['payment_back'], $sale['collection_time'])) {
            if ($sale['payment_back'] == 1){
                $collection['period_id'] = $collection['period_id'].",".$sale_id;
            }
            $period_id = explode(',',$collection['period_id']);
            $period_id = array_unique($period_id);
            if ($period_id[0] == ''){
                unset($period_id[0]);
            }
            if ($sale['payment_back'] == 2){
                $i = array_search($sale_id,$period_id);
                unset($period_id[$i]);
            }
            $period_id = empty($period_id) ? "" : implode(',',$period_id);
            $collection['period_id'] = $period_id;
            $status = $collection['status'];
            $collection['status'] = Collection::STATUS_PROCESSED;
            $collection->save();
            if (empty($collection['period_id'])) {
                $collection['status'] = Collection::STATUS_NOT_PROCESSED;
                $collection->save();
            }
            if ($status != $collection['status'] && $status == Collection::STATUS_NOT_PROCESSED){
                CollectionCurrencyTransactionService::payback($collection['collection_bank_id'], $collection['collection_amount'],$collection['id']);
            }elseif ($status != $collection['status'] && $status == Collection::STATUS_PROCESSED){
                CollectionCurrencyTransactionService::payback($collection['collection_bank_id'], $collection['collection_amount'] * (-1),$collection['id']);
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "更新失败", []);
        }
    }


    /**
     * @routeName 修改状态
     * @routeDescription 修改状态
     * @return array
     * @throws
     */
    public function actionUpdateStatus(){
        $req = Yii::$app->request;
        Yii::$app->response->format = Response::FORMAT_JSON;
        $collection_id = $req->get('collection_id');
        $model = $this->findModel($collection_id);
        $model['status'] = $model['status'] == 1 ? 2 : 1;
        if ($model->save()){
            return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
        }else{
            return $this->FormatArray(self::REQUEST_FAIL, "更新失败", []);
        }
    }

    /**
     * @routeName 批量回款
     * @routeDescription 批量回款
     * @return array
     * @throws
     */
    public function actionBatchCollection(){
        $req = Yii::$app->request;
        $collection = $req->get('collection_id');
        $model = $this->findModel($collection);
        if ($req->isPost){
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $sale_id = $post['sales_id'];
            $model['period_id'] = $model['period_id'].",".implode(',',$post['sales_id']);
            foreach ($sale_id as $v){
                $sale = FinancialPlatformSalesPeriod::findOne($v);
                $sale['collection_time'] = $model['collection_date'];
                if ((new OrderSettlementService())->collectionStatus($v,1,$model['collection_date'])){
                }else{
                    return $this->FormatArray(self::REQUEST_FAIL, "回款失败", []);
                }
            }
            if ($model['status'] == Collection::STATUS_NOT_PROCESSED){
                CollectionCurrencyTransactionService::payback($model['collection_bank_id'], $model['collection_amount'],$model['id']);
            }
            $model['status'] = Collection::STATUS_PROCESSED;
            $period_id = explode(',',$model['period_id']);
            if ($period_id[0] == ''){
                unset($period_id[0]);
            }
            $period_id = implode(',',$period_id);
            $model['period_id'] = $period_id;
            $model->save();
            return $this->FormatArray(self::REQUEST_SUCCESS, "回款成功", []);
        }
        return $this->render('batch_collection');
    }

    /**
     * 获取月份
     */
    function get_month($month)
    {
        $monthEng = [
            "Jan" => 1,
            "Feb" => 2,
            "Mar" => 3,
            "Apr" => 4,
            "May" => 5,
            "Jun" => 6,
            "Jul" => 7,
            "Aug" => 8,
            "Sep" => 9,
            "Oct" => 10,
            "Nov" => 11,
            "Dec" => 12,
        ];
        return $monthEng[$month] ?? "";
    }


    /**
     * 获取平台
     */
    function getPlatform($type){
        $platform = [
            "Hepsiburada" => Base::PLATFORM_HEPSIGLOBAL,
            "Linio Mexico" => Base::PLATFORM_LINIO,
            "Linio Colombia" => Base::PLATFORM_LINIO,
            'nocnoc' => Base::PLATFORM_NOCNOC,
        ];
        return $platform[$type] ?? "";
    }

    /**
     * Finds the Collection model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Collection the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Collection::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
