<?php

namespace backend\controllers;

use common\base\BaseController;
use common\components\statics\Base;
use common\models\Category;
use common\services\independence_category\IndependenceCategoryService;
use Yii;
use common\models\IndependenceCategory;
use backend\models\search\IndependenceCategorySearch;
use yii\web\NotFoundHttpException;
use yii\web\Response;


class IndependenceCategoryController extends BaseController
{
    public function model()
    {
        return new IndependenceCategory();
    }

    /**
     * @routeName 独立站分类主页
     * @routeDescription 独立站分类主页
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $platform_type = $req->get('platform_type', 55);
        return $this->render('index', ['platform_type' => $platform_type]);
    }

    /**
     * @routeName 独立站分类列表
     * @routeDescription 独立站分类列表
     */
    public function actionList()
    {
        $req = Yii::$app->request;
        $platform_type = $req->get('platform_type', Base::PLATFORM_WOOCOMMERCE);
        Yii::$app->response->format = Response::FORMAT_JSON;
        $searchModel = new IndependenceCategorySearch();
        $where = $searchModel->search(Yii::$app->request->queryParams, $platform_type);
        $data = $this->lists($where, 'sort desc,id desc');
        foreach ($data['list'] as &$info) {
            $category = Category::findOne($info['category_id']);
            $info['has_child'] = $info['has_child'] == 0 ? '无' : '有';
            $info['parent_name'] = IndependenceCategory::getCategoryNamesTreeByCategoryId($info['parent_id']);
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS, "获取成功", $data['list'], $data['pages']->totalCount);
    }


    /**
     * @routeName 获取分类
     * @routeDescription 获取分类
     */
    public function actionGetTreeCategoryOpt()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $req = \Yii::$app->request;
        $platform_type = $req->get('platform_type', 55);
        $parent_id = $req->get('parent_id', 0);
        $independence_category = IndependenceCategory::find()->select('name,id,parent_id,has_child')
            ->andWhere(['parent_id' => $parent_id])->orderBy('sort desc,id desc');
        if (!empty($platform_type)) {
            $independence_category = $independence_category->andWhere(['=', 'platform_type', $platform_type]);
        }

        $category_arr = $independence_category->asArray()->all();
        foreach ($category_arr as &$v) {
            $category_count = IndependenceCategory::find()->where(['parent_id' => $v['id']])->count();
            $str = '(' . $category_count . ')';
            $v['name'] = $v['name'] . $str;
            if ($v['has_child'] == 1) {
                $v['isParent'] = true;
            }
        }
        if (empty($parent_id)) {
            $category_arr = ['id' => 0, 'name' => '根', 'parent_id' => 0, 'isParent' => true, 'children' => $category_arr, 'open' => true];
        }
        return $category_arr;
    }


    /**
     * @routeName 新增独立站分类
     * @routeDescription 创建独立站分类
     * @return string |Response |array
     * @throws
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $platform_type = $req->get('platform_type');
        $parent_id = $req->get('parent_id');
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $model = new IndependenceCategory();
            $model['category_id'] = empty($post['category_id']) ? 0 : $post['category_id'];
            $model['parent_id'] = $post['parent_id'];
            $model['name'] = $post['name'];
            $model['name_en'] = $post['name_en'];
            $model['platform_type'] = $post['platform_type'];
            $model['status'] = IndependenceCategory::STATUS_NORMAL;
            $model['sort'] = $post['sort'];
            if (empty($post['name']) || empty($post['name_en'])) {
                if (empty($post['category_id'])) {
                    return $this->FormatArray(self::REQUEST_FAIL, '类目名称或者类目名称(EN)不能为空');
                }
                $category = Category::findOne($post['category_id']);
                if (empty($post['name'])) {
                    $model['name'] = $category['name'];
                }
                if (empty($post['name_en'])) {
                    $model['name_en'] = $category['name_en'];
                }
            }
            $where = [];
            $where['name'] = $model['name'];
            $where['platform_type'] = $model['platform_type'];
            $exist = IndependenceCategory::find()->where($where)->select('id')->exists();
            if ($exist) {
                return $this->FormatArray(self::REQUEST_FAIL, '添加失败：类目名已经存在', []);
            }
            if ($model->save()) {
                if ($model['parent_id'] != 0) {
                    IndependenceCategory::updateAll(['has_child' => 1], ['id' => $model['parent_id']]);
                }
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, '添加失败', []);
            }
        }
        return $this->render('create', ['platform_type' => $platform_type, 'parent_id' => $parent_id]);
    }

    /**
     * @routeName 更新独立站分类
     * @routeDescription 更新独立站分类
     * @throws
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $id = $req->get('category_id');
        if ($req->isPost) {
            $id = $req->post('id');
        }
        $model = $this->findModel($id);
        $parent_category = IndependenceCategory::findOne($model['parent_id']);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $model['category_id'] = empty($post['category_id']) ? 0 : $post['category_id'];
            $model['parent_id'] = $post['parent_id'];
            $model['name'] = $post['name'];
            $model['name_en'] = $post['name_en'];
            $model['platform_type'] = $post['platform_type'];
            $model['sort'] = $post['sort'];
            if (empty($post['name']) || empty($post['name_en'])) {
                if (empty($post['category_id'])) {
                    return $this->FormatArray(self::REQUEST_FAIL, '类目名称或者类目名称(EN)不能为空');
                }
                $category = Category::findOne($post['category_id']);
                if (empty($post['name'])) {
                    $model['name'] = $category['name'];
                }
                if (empty($post['name_en'])) {
                    $model['name_en'] = $category['name_en'];
                }
            }
            $where = [];
            $where['name'] = $model['name'];
            $where['platform_type'] = $model['platform_type'];
            if ($where['name'] != $model['name'] && !empty($model['name'])) {
                $exist = IndependenceCategory::find()->where($where)->select('id')->exists();
                if ($exist) {
                    return $this->FormatArray(self::REQUEST_FAIL, '添加失败：类目名已经存在', []);
                }
            }
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, '更新失败', []);
            }
        } else {
            return $this->render('update', ['info' => $model->toArray(), 'parent_name' => $parent_category['name']]);
        }
    }

    /**
     * @routeName 删除独立站分类
     * @routeDescription 删除独立站分类
     * @return array
     * @throws
     */
    public function actionDelete()
    {
        $req = \Yii::$app->request;
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $category_id = $req->get('category_id');
        if (empty($category_id)) {
            return $this->FormatArray(self::REQUEST_FAIL, '删除失败', []);
        }

        $categoryInfo = IndependenceCategory::findOne(['id' => $category_id]);
        if (empty($categoryInfo)) {
            return $this->FormatArray(self::REQUEST_FAIL, '删除失败', []);
        }

        if (IndependenceCategory::findOne(['parent_id' => $categoryInfo->id])) {
            return $this->FormatArray(self::REQUEST_FAIL, '删除失败,无法删除子分类', []);
        }

        $mapping = (int)$categoryInfo['mapping'];
        if ($categoryInfo->delete()) {
            IndependenceCategoryService::deleteWooCategory($mapping);
            return $this->FormatArray(self::REQUEST_SUCCESS, '删除成功', []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, '删除失败', []);
        }
    }

    /**
     * @routeName 独立站类目映射
     * @routeDescription 独立站类目映射
     * @return array
     * @throws
     */
    public function actionMapping()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $model = $this->findModel($id);
        if ($req->isPost) {
            \Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $exists = false;
            $independence_category = IndependenceCategory::findOne($post['id']);
            $independence_category['mapping'] = $post['mapping'];
            if ($model['platform_type'] == Base::PLATFORM_WOOCOMMERCE) {
                $exists = IndependenceCategoryService::existsWooCategory($post['mapping']);
            }
            if ($exists) {
                $independence_category->save();
                return $this->FormatArray(self::REQUEST_SUCCESS, '映射成功', []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, '映射失败,未找到类目', []);
            }
        }
        return $this->render('mapping', ['info' => $model]);
    }


    /**
     * @routeName 独立站类目同步
     * @routeDescription 独立站类目同步
     * @return array
     * @throws
     */
    public function actionInit()
    {
        $req = Yii::$app->request;
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $platform_type = $req->get('platform_type');
        if (IndependenceCategoryService::syncWooCategory($platform_type)) {
            return $this->FormatArray(self::REQUEST_SUCCESS, '同步成功', []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, '同步失败', []);
        }
    }

    /**
     * Finds the IndependenceCategory model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return IndependenceCategory the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = IndependenceCategory::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
