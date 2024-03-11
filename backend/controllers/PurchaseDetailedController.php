<?php

namespace backend\controllers;

use common\base\BaseController;
use common\components\statics\Base;
use common\services\ImportResultService;
use moonland\phpexcel\Excel;
use Yii;
use common\models\purchase\PurchaseDetailed;
use backend\models\search\PurchaseDetailedSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\web\UploadedFile;


class PurchaseDetailedController extends BaseController
{
    public function model()
    {
        return new PurchaseDetailed();
    }

    /**
     * @routeName 采购订单主页
     * @routeDescription 采购订单主页
     */
    public function actionIndex()
    {
        $searchModel=new PurchaseDetailedSearch();
        $param = Yii::$app->request->queryParams;
        $where = $searchModel->search($param);
        $data = $this->lists($where);
        foreach ($data['list'] as &$info){
            $info['add_time'] = Yii::$app->formatter->asDatetime($info['add_time'],'php:Y-m-d H:i');
            $info['create_date'] = Yii::$app->formatter->asDatetime($info['create_date'],'php:Y-m-d H:i');
            $info['deiburse_date'] = $info['deiburse_date'] == 0 ? '' : Yii::$app->formatter->asDatetime($info['deiburse_date'],'php:Y-m-d H:i');
            $info['source'] = Base::$purchase_source_maps[$info['source']];
            $info['status'] = PurchaseDetailed::$status_maps[$info['status']];
        }
        return $this->render('index',[
            'searchModel' => $searchModel,
            'list' => $data['list'],
            'pages' => $data['pages'],
        ]);
    }

    /**
     * @routeName 导入采购商品订单
     * @routeDescription 导入采购商品订单
     * @return array
     * @throws
     */
    public function actionImportRelation()
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
            'relation_no' => '订单编号',
            'freight' => '运费(元)',
            'disburse_amount' => '实付款(元)',
            'create_date' => '订单创建时间',
            'deiburse_date' => '订单付款时间',
            'desc' => '买家留言',
            'relation_no_status' => '订单状态',
            'company' => '卖家公司名',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }

        if(empty($keyMap['relation_no']) || empty($keyMap['freight']) || empty($keyMap['disburse_amount']) || empty($keyMap['create_date']) || empty($keyMap['deiburse_date'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }

        if(empty($keyMap['relation_no_status']) || empty($keyMap['company'])) {
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

            if (empty($relation_no)){
                continue;
            }

            $purchase = PurchaseDetailed::findOne(['relation_no'=>$relation_no]);
            if (!empty($purchase)){
                $errors[$i] = '订单编号已经存在';
                continue;
            }

            $status = PurchaseDetailed::getStatus($relation_no_status);
            if ($status === false){
                continue;
            }

            if ((empty($create_date))) {
                $errors[$i] = '订单创建时间不能为空';
                continue;
            }

            if ((empty($freight) || empty($disburse_amount))) {
                $errors[$i] = '运费(元)和实付款(元)不能为空';
                continue;
            }

            $create_date = $this->strDate($create_date);
            $deiburse_date = $this->strDate($deiburse_date);
            $disburse_amount = str_replace(',','',$disburse_amount);
            try {
                $model = new PurchaseDetailed();
                $model['relation_no'] = $relation_no;
                $model['goods_amount'] = $disburse_amount - $freight;
                $model['freight'] = (float)$freight;
                $model['disburse_amount'] = (float)$disburse_amount;
                $model['create_date'] = strtotime($create_date);
                $model['deiburse_date'] = empty($deiburse_date) ? 0 : strtotime($deiburse_date);
                $model['desc'] = $desc;
                $model['source'] = Base::PLATFORM_1688;
                $model['status'] = $status;
                $model['company'] = $company;
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
                $info['rvalue1'] = empty($row[$keyMap['create_date']])?'':$row[$keyMap['create_date']];
                $info['rvalue2'] = empty($row[$keyMap['deiburse_date']])?'':$row[$keyMap['deiburse_date']];
                $info['rvalue3'] = empty($row[$keyMap['freight']])?'':$row[$keyMap['freight']];
                $info['rvalue4'] = empty($row[$keyMap['disburse_amount']])?'':$row[$keyMap['disburse_amount']];
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('采购订单', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "导入成功", []);
    }


    //转换时间
    public function strDate($date)
    {
        $date = str_replace([',','/'],['','-'],$date);
        if(strlen($date) > 16){
            $date = substr($date , 0 , 16);
        }
        return $date;
    }

    /**
     * Finds the PurchaseDetailed model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return PurchaseDetailed the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = PurchaseDetailed::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
