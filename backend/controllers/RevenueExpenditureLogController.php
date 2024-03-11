<?php

namespace backend\controllers;

use common\base\BaseController;
use common\models\Reimbursement;
use common\models\RevenueExpenditureAccount;
use common\models\RevenueExpenditureType;
use common\models\User;
use Yii;
use common\models\RevenueExpenditureLog;
use backend\models\search\RevenueExpenditureLogSearch;
use yii\web\NotFoundHttpException;
use yii\web\Response;


class RevenueExpenditureLogController extends BaseController
{
    public function model()
    {
        return new RevenueExpenditureLog();
    }


    public function actionIndex()
    {
        $first_account = RevenueExpenditureAccount::find()->asArray()->one();
        $searchModel=new RevenueExpenditureLogSearch();
        $param = Yii::$app->request->queryParams;
        $bank_balance = 0;
        if (empty($param['RevenueExpenditureLogSearch'])){
            $param['RevenueExpenditureLogSearch']['revenue_expenditure_account_id'] = $first_account['id'];
        }
        $where = $searchModel->search($param);
        $data = $this->lists($where);
        $revenue_account = RevenueExpenditureAccount::getAllAccount();
        $revenue_type = RevenueExpenditureType::getAllType();
        $amount = RevenueExpenditureLog::dealWhere($where)
            ->select('sum(case when money> 0 then money else 0 end) as earn,sum(case when money <0 then money else 0 end ) as lose')
            ->asArray()->one();
        $revenue_expenditure_account_id = isset($where['revenue_expenditure_account_id']) ? $where['revenue_expenditure_account_id'] : 0;
        if ($revenue_expenditure_account_id != 0){
            $account = RevenueExpenditureAccount::findOne($revenue_expenditure_account_id);
            $bank_balance = $account['amount'];
        }
        $bank_balance = number_format($bank_balance,2);
        $earn = number_format($amount['earn'],2);
        $lose = number_format($amount['lose'],2);
        $map = Reimbursement::getAllReimbursement();
        foreach ($data['list'] as &$info){
            $image = json_decode($info['images'], true);
            $info['img'] = $image;
            $info['add_time'] = Yii::$app->formatter->asDatetime($info['add_time']);
            $info['update_time'] = Yii::$app->formatter->asDatetime($info['update_time']);
            $info['date'] = Yii::$app->formatter->asDate($info['date']);
            $info['examine'] = RevenueExpenditureLog::$examine_maps[$info['examine']];
            $info['revenue_expenditure_account'] = empty($revenue_account[$info['revenue_expenditure_account_id']]) ? '' : $revenue_account[$info['revenue_expenditure_account_id']];
            $info['revenue_expenditure_type'] = empty($revenue_type[$info['revenue_expenditure_type']]) ? '' : $revenue_type[$info['revenue_expenditure_type']];
            $info['admin_id'] = User::getInfoNickname($info['admin_id']);
            if(!empty($info['reimbursement_id'])){ $info['reimbursement_id'] = $map[$info['reimbursement_id']];}
            $info['total_amount'] = number_format(round($info['money'] + $info['org_money'],2),2);
            $info['money'] = number_format($info['money'],2);
            $info['payment_back_status'] = RevenueExpenditureLog::$payment_back_maps[$info['payment_back']];
            if(empty($info['reimbursement_id'])){$info['reimbursement_id']='';}
        }
        return $this->render('index',[
            'searchModel' => $searchModel,
            'list' => $data['list'],
            'pages' => $data['pages'],
            'earn'=>$earn,
            'lose'=>$lose,
            'bank_balance'=>$bank_balance,
        ]);
    }

    /**
     * @routeName 新增账号明细日志
     * @routeDescription 创建账号明细日志
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $user_id = Yii::$app->user->identity->id;
        $transaction = Yii::$app->db->beginTransaction();
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $money = str_ireplace(',','',trim($post['money']));
            $model = new RevenueExpenditureLog();
            $revenue_account = RevenueExpenditureAccount::findOne($post['revenue_expenditure_account_id']);
            $model['org_money'] = $revenue_account['amount'];
            $model['date'] = strtotime($post['date']);
            $model['revenue_expenditure_account_id'] = $post['revenue_expenditure_account_id'];
            $model['examine'] = $post['examine'];
            $model['revenue_expenditure_type'] = $post['revenue_expenditure_type'];
            $model['desc'] = $post['desc'];
            $model['money'] = (float)$money;
            $model['admin_id'] = $user_id;
            $model['images'] = $post['images'];
            $model['payment_back'] = $post['payment_back'];
            $model['reimbursement_id'] = $post['reimbursement_id'];
            try {
                $model->save();
                $revenue_account['amount'] = $revenue_account['amount'] + (float)$money;
                $revenue_account->save();
                $transaction->commit();
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } catch (\Exception $e) {
                $transaction->rollBack();
                return $this->FormatArray(self::REQUEST_FAIL, "添加失败,检查是否有空格或者格式错误", []);
            }
        }
        return $this->render('create');
    }

    /**
     * @routeName 更新账号明细日志
     * @routeDescription 更新账号明细日志
     * @throws
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $user_id = Yii::$app->user->identity->id;
        if ($req->isPost) {
            $id = $req->post('id');
        }
        $revenue_account = RevenueExpenditureAccount::getAllAccount();
        $model = $this->findModel($id);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $model['date'] = strtotime($post['date']);
            $model['examine'] = $post['examine'];
            $model['revenue_expenditure_type'] = $post['revenue_expenditure_type'];
            $model['desc'] = $post['desc'];
            $model['admin_id'] = $user_id;
            $model['images'] = $post['images'];
            $model['payment_back'] = $post['payment_back'];
            $model['reimbursement_id'] = $post['reimbursement_id'];
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, '更新失败', []);
            }
        } else {
            return $this->render('update', ['info' => $model->toArray(),'revenue_account'=>$revenue_account]);
        }
    }


    /**
     * @routeName 更改核查状态
     * @routeDescription 更改核查状态
     * @return array
     * @throws
     */
    public function actionPaymentStatus(){
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $model = $this->findModel($id);
        $model['examine'] = $model['examine'] == 2 ? 1 : 2;
        if ($model->save()){
            return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
        }else{
            return $this->FormatArray(self::REQUEST_SUCCESS, "更新失败", []);
        }
    }

    /**
     * @routeName 删除账号明细日志
     * @routeDescription 删除账号明细日志
     * @return array
     * @throws
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $connection = Yii::$app->db;
        $model = $this->findModel($id);
        $delete_id = $model['id'];
        $log_account = RevenueExpenditureAccount::findOne($model['revenue_expenditure_account_id']);
        $revenue_expenditure_account_id = $model['revenue_expenditure_account_id'];
        if ($model->delete()) {
            $sql = 'update ys_revenue_expenditure_log set org_money = org_money - '.$model['money'].' where id > '.$delete_id.' and revenue_expenditure_account_id = '.$revenue_expenditure_account_id;
            $connection->createCommand($sql)->execute();
            $log_account['amount'] = $log_account['amount'] - $model['money'];
            $log_account->save();
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除失败", []);
        }
    }

    /**
     * Finds the RevenueExpenditureLog model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return RevenueExpenditureLog the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = RevenueExpenditureLog::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
