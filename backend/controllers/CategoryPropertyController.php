<?php

namespace backend\controllers;

use backend\models\search\CategoryPropertySearch;
use common\models\Category;
use common\models\CategoryPropertyValue;
use common\models\GoodsProperty;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use common\base\BaseController;
use Yii;
use common\models\CategoryProperty;
use yii\web\NotFoundHttpException;

class CategoryPropertyController extends BaseController
{
    const PROPERTY_TYPE_ONE = 'select';
    const PROPERTY_TYPE_TWO = 'radio';
    const PROPERTY_TYPE_TRE = 'text';
    const PROPERTY_TYPE_FOR = 'size';
    const ONE = 1;
    const TWO = 2;

    public static $map = [
        self::PROPERTY_TYPE_ONE => '下拉',
        self::PROPERTY_TYPE_TWO => '单选',
        self::PROPERTY_TYPE_TRE => '文本框',
        self::PROPERTY_TYPE_FOR => '尺寸'
    ];
    public static $map_two = [
        self::ONE => '是',
        self::TWO => '否'
    ];
    public static $map_tre = [
        self::ONE => '启用',
        self::TWO => '禁用'
    ];

    public function model()
    {
        return new CategoryProperty();
    }

    /**
     * @routeName 类目属性主页
     * @routeDescription 类目属性主页
     */
    public function actionIndex()
    {
        $category = Category::find()->where(['parent_id' => 0])->select('id')->asArray()->all();
        $category_id = ArrayHelper::getColumn($category,'id');
        $category_lists = Category::find()->select('name,id')->where(['source_method'=>1,'id'=>$category_id])->asArray()->all();
        $category_arr  =[];
        foreach ($category_lists as $v){
            $category_arr[$v['id']] = $v['name'];
        }
        return $this->render('index',['category_arr' => $category_arr]);
    }

    /**
     * @routeName 类型列表
     * @routeDescription 类型列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel=new CategoryPropertySearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where,'sort desc,id asc');
        foreach ($data['list'] as &$info){
            $info['parent_name'] = Category::getCategoryNamesTreeByCategoryId($info['category_id']);
            $info['property_type'] = empty(CategoryPropertyController::$map[$info['property_type']]) ? '' : CategoryPropertyController::$map[$info['property_type']];
            $info['status'] = empty(CategoryPropertyController::$map_tre[$info['status']]) ? '' : CategoryPropertyController::$map_tre[$info['status']];
            $info['is_multiple'] = empty(CategoryPropertyController::$map_two[$info['is_multiple']]) ? '' : CategoryPropertyController::$map_two[$info['is_multiple']];
            $info['is_required'] = empty(CategoryPropertyController::$map_two[$info['is_required']]) ? '' : CategoryPropertyController::$map_two[$info['is_required']];
            $info['delete'] = GoodsProperty::find()->where(['property_id' => $info['id']])->exists();
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data['list'],$data['pages']->totalCount);
    }

    /**
     * @routeName 类目属性的增加
     * @routeDescription 类目属性的增加
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $is_other = $req->post('is_other');
            $model = new CategoryProperty();
            $model->load($req->post(), '');
            if (empty($model['category_id'])) {
                return $this->FormatArray(self::REQUEST_FAIL, '类目不能为空',[]);
            }
            $name_exists = CategoryProperty::find()->where(['category_id' => $model['category_id'],'property_name' => $model['property_name']])->exists();
            if ($name_exists) {
                return $this->FormatArray(self::REQUEST_FAIL, '该分类已存在此属性',[]);
            }
            if ($model->save()) {
                if ($is_other == CategoryPropertyController::ONE) {
                    $this->operateOther($model);
                }
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        }
        return $this->render('create');
    }

    /**
     * @routeName 更新属性
     * @routeDescription 更新属性
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
            $old_other = $model['custom_property_value_id'] != 0 ? self::ONE : self::TWO;
            $is_other = $req->post('is_other');
            if (empty($post['category_id'])) {
                return $this->FormatArray(self::REQUEST_FAIL, '类目不能为空',[]);
            }
            if ($model['category_id'] != $post['category_id'] || $model['property_name'] != $post['property_name']) {
                $name_exists = CategoryProperty::find()->where(['category_id' => $post['category_id'],'property_name' => $post['property_name']])->exists();
                if ($name_exists) {
                    return $this->FormatArray(self::REQUEST_FAIL, '该分类已存在此属性',[]);
                }
            }
            $model->load($req->post(), '');
            if ($model->save()) {
                if ($old_other != $is_other) {
                    $this->operateOther($model);
                }
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, '更新失败', []);
            }
        } else {
            return $this->render('update', ['info' => $model->toArray()]);
        }
    }

    /**
     * @routeName 类目属性删除
     * @routeDescription 类目属性删除
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $model = $this->findModel($id);
        $exists = GoodsProperty::find()->where(['property_id' => $id])->exists();
        if ($exists) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除失败", []);
        }
        if ($model->delete()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除失败", []);
        }
    }


    /**
     * 操作其他
     * @param  $property
     * @return void
     */
    public function operateOther($property)
    {
        if ($property['custom_property_value_id'] == 0) {
            $property_value = new CategoryPropertyValue();
            $property_value['property_id'] = $property['id'];
            $property_value['property_value'] = '其他';
            $property_value['status'] = 1;
            $property_value->save();

            $property['custom_property_value_id'] = $property_value['id'];
            $property->save();
        } else {
            $property_value = CategoryPropertyValue::find()->where(['id' => $property['custom_property_value_id']])->one();
            if (!empty($property_value)) {
                $property_value->delete();
                $property['custom_property_value_id'] = 0;
                $property->save();
            }
        }
    }

    /**
     * Finds the CategoryProperty model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CategoryProperty the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CategoryProperty::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
