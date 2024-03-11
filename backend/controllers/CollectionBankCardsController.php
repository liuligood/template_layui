<?php

namespace backend\controllers;

use common\base\BaseController;
use common\models\financial\Collection;
use common\models\financial\CollectionAccount;
use common\models\Shop;
use common\services\ImportResultService;
use moonland\phpexcel\Excel;
use Yii;
use common\models\financial\CollectionBankCards;
use backend\models\search\CollectionBankCardsSearch;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\web\UploadedFile;

class CollectionBankCardsController extends BaseController
{
    public function model()
    {
        return new CollectionBankCards();
    }


    /**
     * @routeName 银行卡表主页
     * @routeDescription 银行卡表主页
     * @throws
     * @return string |Response |array
     */
    public function actionIndex()
    {
        return $this->render('index');
    }


    /**
     * @routeName 银行卡列表
     * @routeDescription 银行卡列表
     * @throws
     * @return string |Response |array
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel =new CollectionBankCardsSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);
        foreach ($data['list'] as &$info){
            $info['add_time'] = Yii::$app->formatter->asDatetime($info['add_time']);
            $info['update_time'] = Yii::$app->formatter->asDatetime($info['update_time']);
            $collection = CollectionAccount::findOne($info['collection_account_id']);
            $info['collection_account'] = $collection['collection_account'];
            $info['shop_name'] = CollectionBankCards::getShopName($info['id']);
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data['list'],$data['pages']->totalCount);
    }

    /**
     * @routeName 新增银行卡
     * @routeDescription 创新增银行卡
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $where = [];
            $where['collection_bank_cards'] = $post['collection_bank_cards'];
            $where['collection_account_id'] = $post['collection_account_id'];
            $bank = CollectionBankCards::find()->where($where)->asArray()->all();
            if (!empty($bank)){
                return $this->FormatArray(self::REQUEST_FAIL, "该账号已经绑定该银行卡", []);
            }
            $model = new CollectionBankCards();
            $model['collection_account_id'] = $post['collection_account_id'];
            $model['collection_bank_cards'] = $post['collection_bank_cards'];
            $model['collection_currency'] = $post['collection_currency'];
            $account = CollectionAccount::findOne($model['collection_account_id']);
            $shop = Shop::findOne($post['shop_id']);
            if ($shop['collection_account'] == $account['collection_account'] || empty($shop['collection_account'])){
                if ($shop['collection_bank_cards_id'] != 0){
                    return $this->FormatArray(self::REQUEST_FAIL, "该店铺已存在账号和银行卡", []);
                }
                $model->save();
                if (!empty($post['shop_id'])){
                    $shop['collection_account_id'] = $post['collection_account_id'];
                    $shop['collection_bank_cards_id'] = $model['id'];
                    $shop->save();
                }
            }else{
                return $this->FormatArray(self::REQUEST_FAIL, "店铺跟收款账号不匹配", []);
            }
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
        }
        return $this->render('create');
    }

    /**
     * @routeName 更新银行卡信息
     * @routeDescription 更新银行卡信息
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
            $post = $req->post();
            $where = [];
            $where['collection_bank_cards'] = $post['collection_bank_cards'];
            $where['collection_account_id'] = $post['collection_account_id'];
            $bank = CollectionBankCards::find()->where($where)->asArray()->all();
            $bank_cards = ArrayHelper::getColumn($bank,'collection_bank_cards');
            if (!empty($bank) && !in_array($model['collection_bank_cards'],$bank_cards)){
                return $this->FormatArray(self::REQUEST_FAIL, "该账号已经绑定该银行卡", []);
            }
            $model['collection_account_id'] = $post['collection_account_id'];
            $model['collection_bank_cards'] = $post['collection_bank_cards'];
            $model['collection_currency'] = $post['collection_currency'];
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
     * @routeName 删除银行卡
     * @routeDescription 删除银行卡
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
     * @routeName 导入银行卡
     * @routeDescription 导入银行卡
     * @return array
     * @throws
     */
    public function actionImportBank()
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
            'collection_account' => '收款账号',
            'collection_bank_cards' => '收款银行卡',
            'collection_currency' => '收款币种',
            'shop_name' => '店铺名',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }

        if(empty($keyMap['collection_account']) || empty($keyMap['collection_bank_cards']) || empty($keyMap['collection_currency']) || empty($keyMap['shop_name'])) {
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

            if ((empty($collection_account) || empty($collection_bank_cards))) {
                $errors[$i] = '收款账号和收款银行卡不能为空';
                continue;
            }

            if ((empty($collection_currency) || empty($shop_name))) {
                $errors[$i] = '收款币种和店铺名不能为空';
                continue;
            }

            try {
                $account = CollectionAccount::find()->where(['collection_account'=>$collection_account])->select(['id','collection_account'])->asArray()->one();
                $shop = Shop::find()->where(['name'=>$shop_name])->one();
                if (empty($shop)){
                    $errors[$i] = '收款账号跟店铺不匹配';
                    continue;
                }
                $model = CollectionBankCards::find()
                    ->where(['collection_account_id'=>$account['id'],'collection_bank_cards'=>$collection_bank_cards])->one();
                if(empty($model)) {
                    $model = new CollectionBankCards();
                    $model['collection_account_id'] = $account['id'];
                    $model['collection_bank_cards'] = $collection_bank_cards;
                }
                $model['collection_currency'] = $collection_currency;
                $model->save();
                if($shop['collection_bank_cards_id'] != $model['id']) {
                    if ($shop['collection_bank_cards_id'] != 0) {
                        $errors[$i] = '该店铺已存在账号和银行卡';
                        continue;
                    }
                    $shop['collection_account_id'] = $account['id'];
                    $shop['collection_bank_cards_id'] = $model['id'];
                    $shop->save();
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
                $info['rvalue1'] = empty($row[$keyMap['collection_account']])?'':$row[$keyMap['collection_account']];
                $info['rvalue2'] = empty($row[$keyMap['collection_bank_cards']])?'':$row[$keyMap['collection_bank_cards']];
                $info['rvalue3'] = empty($row[$keyMap['collection_currency']])?'':$row[$keyMap['collection_currency']];
                $info['rvalue4'] = empty($row[$keyMap['shop_name']])?'':$row[$keyMap['shop_name']];
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('收款银行卡', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "导入成功", []);
    }



    /**
     * Finds the CollectionBankCards model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CollectionBankCards the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CollectionBankCards::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
