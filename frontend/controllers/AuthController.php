<?php

namespace frontend\controllers;

use common\components\statics\Base;
use common\models\Shop;
use common\services\FApiService;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class AuthController extends Controller
{
    /*public function actions()
    {
        $data = GoodsShop::find()->where(['platform_type'=>Base::PLATFORM_TIKTOK])->asArray()->all();
        $result = [];
        foreach($data as $v) {
            $platform_name = Base::$platform_maps[$v['platform_type']];
            $action = strtolower($platform_name).'_'.$v['id'];
            $info = [
                'class'=>'frontend\actions\''.$platform_name.'Action',
                'id' => $v['id'],
            ];
            $result[$action] =  $info;
        }
        return $result;
    }*/

    /**
     * tikiok
     */
    public function actionTiktok()
    {
        return $this->initAccessToken(Base::PLATFORM_TIKTOK);
    }

    /**
     * allegro
     */
    public function actionAllegro()
    {
        return $this->initAccessToken(Base::PLATFORM_ALLEGRO);
    }

    /**
     * Mercado
     */
    public function actionMercado()
    {
        return $this->initAccessToken(Base::PLATFORM_MERCADO);
    }

    /**
     * Microsoft
     */
    public function actionMicrosoft()
    {
        return $this->initAccessToken(Base::PLATFORM_MICROSOFT);
    }

    /**
     * @param $platform_type
     * @return void|\yii\web\Response
     * @throws NotFoundHttpException
     * @throws \yii\base\Exception
     */
    public function initAccessToken($platform_type)
    {
        $req = \Yii::$app->request;
        $id = $req->get('id');
        $shop = Shop::find()->where(['platform_type' => $platform_type, 'id' => $id])->asArray()->limit(1)->one();
        if (empty($shop)) {
            throw new NotFoundHttpException('404 Not Found');
        }
        $code = $req->get('code');
        $api_ser = FApiService::factory($shop);
        if (!empty($code)) {
            $result = $api_ser->initAccessToken($code);
            if ($result) {
                //tiktok设置店铺id
                if ($platform_type == Base::PLATFORM_TIKTOK) {
                    $api_ser->setParamShopId();//设置店铺id
                }
                echo '授权成功';
                exit();
            } else {
                //throw new \Exception('获取token失败');
                echo '获取token失败';
                exit();
            }
        }
        return $this->redirect($api_ser->getAuthUrl());
    }

}