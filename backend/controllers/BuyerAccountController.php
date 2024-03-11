<?php

namespace backend\controllers;

use backend\models\search\BuyerAccountSearch;
use common\components\statics\Base;
use common\models\BuyerAccount;
use common\models\BuyerAccountTransactionLog;
use common\services\buyer_account\BuyerAccountTransactionService;
use common\services\ImportResultService;
use moonland\phpexcel\Excel;
use Yii;
use common\base\BaseController;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class BuyerAccountController extends BaseController
{
    /**
     * 获取model
     * @return \common\models\BaseAR
     */
    public function model(){
        return new BuyerAccount();
    }

    /**
     * @routeName 买家账号管理
     * @routeDescription 买家账号管理
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * @routeName 买家账号列表
     * @routeDescription 买家账号列表
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $searchModel = new BuyerAccountSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);

        $lists = array_map(function ($info) {
            $info['consume_amount'] -= BuyerAccountTransactionLog::find()->where(['type'=>BuyerAccountTransactionService::TYPE_ORDER,'buyer_id'=>$info['buyer_id']])->sum('money');
            $info['card_type_desc'] = empty($info['card_type'])?'':BuyerAccountTransactionService::$card_type_map[$info['card_type']];
            $info['platform_desc'] = Base::$buy_platform_maps[$info['platform']];
            $info['become_member_time_desc'] = empty($info['become_member_time'])?'':date('Y-m-d',$info['become_member_time']);
            $info['status_desc'] = BuyerAccount::$status_map[$info['status']];
            $info['member_desc'] = BuyerAccount::$member_map[$info['member']];
            return $info;
        }, $data['list']);

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $data['pages']->totalCount
        );
    }

    /**
     * @routeName 更新买家账号
     * @routeDescription 更新买家账号信息
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
            $model['become_member_time'] = empty($model['become_member_time'])?'':date('Y-m-d',$model['become_member_time']);
            return $this->render('update', ['model' => $model]);
        }
    }

    /**
     * @routeName 新增买家账号
     * @routeDescription 创建新的买家账号
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $model = new BuyerAccount();
        if ($req->isPost) {
            $data = $req->post();
            $data = $this->dataDeal($data);
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($model->load($data, '') && $model->save()) {
                BuyerAccountTransactionService::initAccount($model['ext_no'],$model->card_type);
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        }
        return $this->render('update',['model' => $model]);
    }

    /**
     *
     * @param $data
     * @return mixed
     */
    private function dataDeal($data)
    {
        $data['become_member_time'] = empty($data['become_member_time']) ? 0 : strtotime($data['become_member_time']);
        return $data;
    }

    /**
     * @param $id
     * @return null|BuyerAccount
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = BuyerAccount::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * @routeName 买家账号导出
     * @routeDescription 买家账号导出
     * @return array |Response|string
     */
    public function actionExport()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $searchModel=new BuyerAccountSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $list = BuyerAccount::getAllByCond($where);
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
            $data[$k]['ext_no'] = $v['ext_no'];
            $data[$k]['platform_desc'] = Base::$buy_platform_maps[$v['platform']];
            $data[$k]['amazon_account'] = $v['amazon_account'];
            $data[$k]['amazon_password'] = $v['amazon_password'];
            $data[$k]['username'] = $v['username'];
            //$data[$k]['card_amount'] = $v['card_amount'];
            //$data[$k]['bcard_amount'] = $v['bcard_amount'];
            $data[$k]['card_type_desc'] = empty($v['card_type'])?'':BuyerAccountTransactionService::$card_type_map[$v['card_type']];
            $data[$k]['amount'] = $v['amount'];
            $data[$k]['member_desc'] = BuyerAccount::$member_map[$v['member']];
            $data[$k]['become_member_time_desc'] = empty($v['become_member_time'])?'':date('Y-m-d',$v['become_member_time']);
            $data[$k]['swipe_num'] = $v['swipe_num'];
            $data[$k]['evaluation_num'] = $v['evaluation_num'];
            $data[$k]['status_desc'] = BuyerAccount::$status_map[$v['status']];
            $data[$k]['remarks'] = $v['remarks'];
        }

        $column = [
            'ext_no' => '分机号',
            'platform_desc' => '平台',
            'amazon_account' => '亚马逊邮箱',
            'amazon_password' => '亚马逊密码',
            'username' => '买家用户名',
            //'card_amount'=>'礼品卡余额',
            //'bcard_amount' => '卡密余额',
            'card_type_desc' => '卡类型',
            'amount' => '余额',
            'member_desc' => '会员',
            'become_member_time_desc' => '激活会员时间',
            'swipe_num' => '刷单数',
            'evaluation_num' => '评价数',
            'status_desc' => '状态',
            'remarks' => '备注',
        ];

        return [
            'key' => array_keys($column),
            'header' => array_values($column),
            'list' => $data,
            'fileName' => '买家账号导出' . date('ymdhis')
        ];
    }

    /**
     * @routeName 买家账号导入
     * @routeDescription 买家账号导入
     * @return array
     * @throws
     */
    public function actionImport()
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
            'platform_desc' => '平台',
            'amazon_account' => '亚马逊邮箱',
            'amazon_password' => '亚马逊密码',
            'username' => '买家用户名',
            'card_type_desc' => '卡类型',
            //'card_amount'=>'礼品卡余额',
            //'bcard_amount' => '卡密余额',
            //'amount' => '余额',
            'member_desc' => '会员',
            'become_member_time_desc' => '激活会员时间',
            'swipe_num' => '刷单数',
            'evaluation_num' => '评价数',
            'remarks' => '备注',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }
        if(empty($keyMap['ext_no']) || empty($keyMap['amazon_account']) || empty($keyMap['amazon_password'])) {
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

            if (empty($ext_no) || empty($platform_desc) || empty($amazon_account) || empty($amazon_password)) {
                $errors[$i] = '分机号、平台、亚马逊邮箱、亚马逊密码不能为空';
                continue;
            }

            try {

                $platform = array_search($platform_desc,Base::$buy_platform_maps);
                if(empty($platform)) {
                    $errors[$i] = '不存在「'.$platform_desc.'」平台';
                    continue;
                }

                $card_type = '';
                if(!empty($card_type_desc)) {
                    $card_type = array_search($card_type_desc, BuyerAccountTransactionService::$card_type_map);
                    if (empty($card_type)) {
                        $errors[$i] = '不存在「' . $card_type_desc . '」卡类型';
                        continue;
                    }
                }

                $member = BuyerAccount::MEMBER_NO;
                if(!empty($member_desc) && $member_desc == '是'){
                    $member = BuyerAccount::MEMBER_YES;
                    if(empty($become_member_time_desc)) {
                        $errors[$i] = '激活会员时间不能为空';
                        continue;
                    }
                }
                $become_member_time = empty($become_member_time_desc) ? 0 : strtotime($become_member_time_desc);

                $buyer_account_data = [
                    'ext_no' => $ext_no,
                    'platform' => $platform,
                    'amazon_account' => $amazon_account,
                    'amazon_password' => $amazon_password,
                    'username' => $username,
                    'card_type' => $card_type,
                    'member' => $member,
                    'become_member_time' => $become_member_time,
                    'swipe_num' => $swipe_num,
                    'evaluation_num' => $evaluation_num,
                    'remarks' => $remarks,
                    'status' => BuyerAccount::STATUS_VALID
                ];

                $model = new BuyerAccount();
                if (!($model->load($buyer_account_data, '') && $model->save())) {
                    $errors[$i] = $model->getErrorSummary(false)[0];
                    continue;
                }
                BuyerAccountTransactionService::initAccount($model['ext_no'],$card_type);
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
            $key = (new ImportResultService())->gen('买家账号', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }

        return $this->FormatArray(self::REQUEST_SUCCESS, "导入成功", []);
    }

}