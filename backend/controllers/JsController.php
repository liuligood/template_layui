<?php

namespace backend\controllers;

use common\base\BaseController;
use common\components\statics\Base;
use common\models\Category;
use common\models\platform\PlatformCategory;
use yii\web\Response;

/**
 * @package backend\controllers
 */
class JsController extends BaseController
{
    /**
     * @routeName 分类
     * @routeDescription 分类
     */
    public function actionGetCategory()
    {
        set_time_limit(0);
        \Yii::$app->response->format=Response::FORMAT_RAW;
        $platform_type = \Yii::$app->request->get('platform_type');
        return 'var category_tree = '. json_encode($this->getCategoryCache($platform_type),JSON_UNESCAPED_UNICODE);
    }

    /**
     * @routeName Ozon分类
     * @routeDescription Ozon分类
     */
    public function actionOzonCategory()
    {
        set_time_limit(0);
        \Yii::$app->response->format=Response::FORMAT_RAW;
        return 'var category_tree = '. json_encode($this->getCategoryCache(Base::PLATFORM_OZON),JSON_UNESCAPED_UNICODE);
    }

    /**
     * @routeName Allegro分类
     * @routeDescription Allegro分类
     */
    public function actionAllegroCategory()
    {
        \Yii::$app->response->format=Response::FORMAT_RAW;
        return 'var category_tree = '. json_encode($this->getCategoryCache(Base::PLATFORM_ALLEGRO),JSON_UNESCAPED_UNICODE);
    }

    public function getCategoryCache($platform_type)
    {
        //$category_key = 'com::platform_category:'.$platform_type;
        //$category = \Yii::$app->redis->get($category_key);
        if (empty($category)) {
            $category = PlatformCategory::find()->select('id,parent_id,name,name_cn')
                ->andWhere(['platform_type' => $platform_type,'status'=>[1,2]])
                ->asArray()->all();
            $category_list = [];
            foreach ($category as $v) {
                $v['name'] = $v['name'] . '(' . $v['name_cn'] . ')';
                unset($v['name_cn']);
                $category_list[] = $v;
            }
            $category = Category::tree($category_list);
            //\Yii::$app->redis->setex($category_key, 24 * 60 * 60, json_encode($category));
        } else {
            $category = json_decode($category, true);
        }
        return $category;
    }

}