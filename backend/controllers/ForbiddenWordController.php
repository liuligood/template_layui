<?php

namespace backend\controllers;

use Yii;
use common\models\ForbiddenWord;
use backend\models\search\ForbiddenWordSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\base\BaseController;
use yii\helpers\ArrayHelper;
use common\components\statics\Base;
use yii\web\Response;
use common\models\User;


class ForbiddenWordController extends BaseController
{


    public function actionIndex()
    {
        return $this->render('index');
    }

    
    
    public function actionList()
    {
        Yii::$app->response->format= Response::FORMAT_JSON;
        $word = new ForbiddenWord();
        $searchModel=new ForbiddenWordSearch();
        $dataProvider=$searchModel->search(Yii::$app->request->queryParams);
        $data=array_values($dataProvider->getModels());
        $data= ArrayHelper::toArray($data);
        $type = Base::$platform_maps + ForbiddenWord::$maps;
        foreach ($data as $key=>$value){
            $data[$key]['add_time'] = Yii::$app->formatter->asDatetime($value['add_time']);
            $data[$key]['update_time'] = Yii::$app->formatter->asDatetime($value['update_time']);
            $data[$key]['platform_type'] = $type[$value['platform_type']];
            $data[$key]['match_model'] = ForbiddenWord::$match_model_maps[$value['match_model']];
            $data[$key]['admin_id'] = User::getInfoNickname($value['admin_id']);
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data,$dataProvider->totalCount);
    }


    /**
     * @routeName 新增违禁词
     * @routeDescription 创建新的违禁词
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $admin_id = Yii::$app->user->identity->id;
            $items = $req->post('word',[]);
            $platform_type = $req->post('platform_type');
            $word_list = ForbiddenWord::find()->where(['word' => $items])->select(['word','platform_type'])->asArray()->all();
            $exist_word = [];
            foreach ($word_list as $v) {
                if ($v['platform_type'] == ForbiddenWord::PLATFORM_ARRAY || $v['platform_type'] == $platform_type) {
                    $exist_word[] = $v['word'];
                }
            }
            if (!empty($exist_word)) {
                $exist_word = implode('，',$exist_word);
                return $this->FormatArray(self::REQUEST_FAIL, '违禁词：' . $exist_word . '已经存在全部或该平台', []);
            }
            foreach ($items as $words) {
                $model = new ForbiddenWord();
                $model->load($req->post(), '');
                $model->word = $words??'';
                $model->admin_id = $admin_id??'';
                if ($model->save()){
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, '添加失败', []);
                }
            }
        return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
        }
        return $this->render('create');
    }


    /**
     * @routeName 更新违禁词
     * @routeDescription 更新违禁词
     * @throws
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $admin_id = Yii::$app->user->identity->id;
        if ($req->isPost) {
            $id = $req->post('id');
        }
        $model = $this->findModel($id);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $word = $req->post('word');
            $platform_type = $req->post('platform_type');
            if ($model['word'] != $word || $model['platform_type'] != $platform_type) {
                if ($model['platform_type'] == ForbiddenWord::PLATFORM_ARRAY && $model['word'] == $word) {
                    $exists = ForbiddenWord::find()->where(['word' => $word,'platform_type' => $platform_type])->exists();
                    if ($exists) {
                        return $this->FormatArray(self::REQUEST_FAIL, "更新失败,全部或者该平台已有违禁词", []);
                    }
                } else {
                    $words = ForbiddenWord::find()->where(['word' => $word])->asArray()->all();
                    $words_platform = ArrayHelper::getColumn($words,'platform_type');
                    if (in_array($platform_type,$words_platform) || in_array(ForbiddenWord::PLATFORM_ARRAY,$words_platform)) {
                        return $this->FormatArray(self::REQUEST_FAIL, "更新失败,全部或者该平台已有违禁词", []);
                    }
                }
            }
            if ($model->load($req->post(), '') == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }
            $model->admin_id = $admin_id??'';
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
     * @routeName 删除违禁词
     * @routeDescription 删除指定违禁词
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

    
    

    protected function findModel($id)
    {
        if (($model = ForbiddenWord::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
