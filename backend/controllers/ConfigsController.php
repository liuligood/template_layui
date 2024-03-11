<?php

namespace backend\controllers;
use backend\models\AdminUser;
use common\base\BaseController;
use DateTime;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Yii;
use common\models\Configs;
use backend\models\search\ConfigsSearch;
use yii\web\Response;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * ConfigsController implements the CRUD actions for Configs model.
 */
class ConfigsController extends BaseController
{
    /**
     * {@inheritdoc}
     */

    /**
     * Lists all Configs models.
     * @return mixed
     *
     * @routeName  系统配置表的主页界面
     * @routeDescription 系统配置表的主页界面
     */
    public function actionIndex()
    {
        $searchModel = new ConfigsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @routeName  系统配置列表
     * @routeDescription 系统配置列表
     */
    public function actionList(){

        Yii::$app->response->format=Response::FORMAT_JSON;

        $searchModel=new ConfigsSearch();
        $dataProvider=$searchModel->search(Yii::$app->request->queryParams);
        foreach ($dataProvider->getModels() as $a){
            $a['add_time'] = date("Y-m-d H:i:s",$a['add_time']);
            $a['update_time'] = date("Y-m-d H:i:s",$a['update_time']);
            $a['admin_id'] = AdminUser::findOne($a['admin_id'])['username'];
        }


        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS,'获取成功',
            $dataProvider->getModels(),$dataProvider->getTotalCount()
        );
    }



    /**
     * Creates a new Configs model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     * @routeName  系统配置表的新增
     * @routeDescription 系统配置表的新增
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new Configs();
            $id = Yii::$app->user->identity->id;
            $model['admin_id']=$id;
            $datetime = time();
            $model['add_time']=$datetime;
            $model['update_time']=$datetime;
            $model->load($req->post(), '');
            $model['type']=Configs::$type_map[$model['type']];
            if ( $model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        }
        return $this->render('create');
    }

    /**
     * Updates an existing Configs model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     * @routeName  系统配置表的编辑
     * @routeDescription 系统配置表的编辑
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            $id = $req->post('id');
        }
        $model = $this->findModel($id);
        $datetime = time();
        $model['update_time']=$datetime;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($model->load($req->post(), '') == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }
            $model['type']=Configs::$type_map[$model['type']];
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        } else {
            return $this->render('update', ['info' => $model->toArray()]);
        }
    }
    public function actionView(){
        $req = Yii::$app->request;
        $searchModel=new ConfigsSearch();
        $dataProvider=$searchModel->search(Yii::$app->request->queryParams);
        if($req->isPost){
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model =$dataProvider->getModels();
        $value = $req->post('val');
        foreach ($model as $list){
          // 使用$req->post("val")获取到传过来的所有信息，而后才进行变量保存
            $config = Configs::findOne($list['id']);
            $val =$value[$list['id']];
            if (Configs::$type_change_map[$config['type']]==Configs::CONFIGS_TYPE_FOURTH){
                $use = '';
                foreach ($val as $v){
                    $use .= $v.',';
                }
                $val =$use;
            }
            $config['value'] =$val;
            $config->save();
        }
            return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
    }

        return $this->render('view', ['model' => $dataProvider->getModels()]);


    }

    /**
     * Deletes an existing Configs model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     * @routeName  系统配置表的删除
     * @routeDescription 系统配置表的删除
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
     * Finds the Configs model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Configs the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Configs::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
