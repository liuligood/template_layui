<?php

namespace backend\controllers;

use backend\models\search\ShelvesSearch;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsStock;
use common\models\warehousing\Shelves;
use common\services\warehousing\ShelvesService;
use Yii;
use common\base\BaseController;
use yii\web\Response;
use yii\web\NotFoundHttpException;

class ShelvesController extends BaseController
{
    /**
     * 获取model
     * @return \common\models\BaseAR
     */
    public function model(){
        return new Shelves();
    }

    /**
     * @routeName 货架管理
     * @routeDescription 货架管理
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * @routeName 货架列表
     * @routeDescription 货架列表
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $searchModel = new ShelvesSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where,'sort desc,id asc');

        $lists = array_map(function ($info) {
            $info['status_desc'] = Shelves::$status_map[$info['status']];
            $info['update_time_desc'] = empty($info['update_time'])?'':date('Y-m-d H:i',$info['update_time']);
            return $info;
        }, $data['list']);

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $data['pages']->totalCount
        );
    }

    /**
     * @routeName 更新货架
     * @routeDescription 更新货架信息
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
            return $this->render('update', ['model' => $model]);
        }
    }

    /**
     * @routeName 批量创建货架
     * @routeDescription 批量创建货架
     * @throws
     * @return string |Response |array
     */
    public function actionBatchCreate()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $data = $req->post();
            $prefix_shelves_no = $data['prefix_shelves_no'];
            $shelves_no_column = $data['shelves_no_column'];
            $shelves_no_row = $data['shelves_no_row'];
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                for ($column_i = 1; $column_i <= $shelves_no_column; $column_i++) {
                    for ($row_i = 1; $row_i <= $shelves_no_row; $row_i++) {
                        $shelves_no = $prefix_shelves_no . '-' . ($column_i < 10 ? ('0' . $column_i) : $column_i) . '-' . ($row_i < 10 ? ('0' . $row_i) : $row_i);
                        $model = new Shelves();
                        $model->shelves_no = $shelves_no;
                        $model->sort = $data['sort'];
                        $model->warehouse = $data['warehouse'];
                        $result = $model->save();
                        if ($result === false) {
                            throw new \Exception($shelves_no . '创建失败 ' . $model->getErrorSummary(false)[0]);
                        }
                    }
                }
                $transaction->commit();
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } catch (\Exception $e) {
                $transaction->rollBack();
                return $this->FormatArray(self::REQUEST_FAIL, $e->getMessage(), []);
            }
        }
        return $this->render('batch-create');
    }

    /**
     * @routeName 创建单个货架
     * @routeDescription 创建新的货架
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $model = new Shelves();
        if ($req->isPost) {
            $data = $req->post();
            $data = $this->dataDeal($data);
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($model->load($data, '') && $model->save()) {
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
        return $data;
    }

    /**
     * @routeName 转移商品
     * @routeDescription 转移商品
     * @throws
     */
    public function actionTransferGoods()
    {
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $model = $this->findModel($id);
        $old_shelves_no = $model['shelves_no'];

        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $model->status = Shelves::STATUS_DEFAULT;
            $model->save();

            $shelves_no = $post['shelves_no'];
            GoodsStock::updateAll(['shelves_no' => $shelves_no], ['shelves_no' => $old_shelves_no]);

            $shelves_service = new ShelvesService();
            $shelves_service->updateStatus($shelves_no);
            return $this->FormatArray(self::REQUEST_SUCCESS, "更换货架成功", []);
        } else {
            $shelves = Shelves::find()->all();
            $shelves_lists = [];
            foreach ($shelves as $v) {
                if ($v['shelves_no'] == $old_shelves_no) {
                    continue;
                }
                $shelves_lists[$v['shelves_no']] = $v['shelves_no'] . '「' . Shelves::$status_map[$v['status']] . '」';
            }
            return $this->render('transfer-goods', ['shelves_lists' => $shelves_lists, 'model' => $model]);
        }
    }

    /**
     * @routeName 删除货架
     * @routeDescription 删除指定货架
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
     * @param $id
     * @return null|Shelves
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = Shelves::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

}