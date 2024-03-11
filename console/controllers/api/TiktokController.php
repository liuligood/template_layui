<?php
namespace console\controllers\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Goods;
use common\models\GoodsShop;
use common\models\Shop;
use common\services\FApiService;
use yii\console\Controller;

class TiktokController extends Controller
{

    public function actionTest($id = null)
    {
        $where = ['platform_type'=>Base::PLATFORM_TIKTOK];
        if(!empty($id)){
            $where['id'] = $id;
        }
        $shop = Shop::findOne($where);
        $api_service = FApiService::factory($shop);
        //$result = $api_service->getOrderLists('2022-06-01','2022-06-02T00:00');
        //$result = $api_service->getTask('497883991');
        //$result = $api_service->getTask('497923957');
        //$result = $api_service->getTask('497958103');
        //$result = $api_service->getGoodsList(1,'VALIDATION_STATE_PENDING');

        ///$result = $api_service->getProductsToAsin('P06569871347279');
        ////$result = $api_service->getCategoryAttributes('17037099');
        //$result = $api_service->getCategory();

        //$result = $api_service->getCategoryProductParameters(261627);
        //$result = $api_service->getCategory();

        //$goods = Goods::find()->where(['goods_no'=>'G06446561834513'])->asArray()->one();

        //$result = $api_service->addGoods($goods);

        //$result = $api_service->updatePrice(['cgoods_no'=>'C06446561837059'],29.99);
        //$result = $api_service->updateStock(['cgoods_no'=>'C06446561837059'],0);

        $goods_shop = GoodsShop::find()->where(['cgoods_no' => 'C06446561837059', 'shop_id' => 180])->one();
        $result = $api_service->delGoods($goods_shop);
        echo CommonUtil::jsonFormat($result);
        exit;
    }

    /**
     * 获取店铺id
     * @param $id
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionSetShopId($id = null)
    {
        $where = ['platform_type'=>Base::PLATFORM_TIKTOK];
        if(!empty($id)){
            $where['id'] = $id;
        }
        $shop = Shop::findOne($where);
        $api_service = FApiService::factory($shop);
        $result = $api_service->setParamShopId();
        echo CommonUtil::jsonFormat($result);
        exit;
    }


    /**
     * 获取店铺
     * @param $id
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionGetAuthorizedShop($id = null)
    {
        $where = ['platform_type'=>Base::PLATFORM_TIKTOK];
        if(!empty($id)){
            $where['id'] = $id;
        }
        $shop = Shop::findOne($where);
        $api_service = FApiService::factory($shop);
        $result = $api_service->getAuthorizedShop();
        echo CommonUtil::jsonFormat($result);
        exit;
    }

    /**
     * 获取分类
     */
    public function actionGetCategory(){
        $where = ['platform_type'=>Base::PLATFORM_TIKTOK];
        $shop = Shop::findOne($where);
        $api_service = FApiService::factory($shop);
        $json = $api_service->getCategory();
        //echo CommonUtil::jsonFormat($json);
        //$json = json_decode($result,true);
        $json = self::tree($json,0);
        foreach ($json as $v1){
            $label_1 = $v1['local_display_name'];
            if(empty($v1['children'])){
                echo $v1['id'].',"'.$label_1."\"".$label_1."\",\"".$label_1."\"\n";
                continue;
            }

            foreach ($v1['children'] as $v2){
                $label_2 = $v2['local_display_name'];
                if(empty($v2['children'])){
                    echo $v2['id'].',"'.$label_1 .' > '.$label_2."\",\"".$label_2."\"\n";
                    continue;
                }
                foreach ($v2['children'] as $v3){
                    $label_3 = $v3['local_display_name'];
                    if(empty($v3['children'])){
                        echo $v3['id'].',"'.$label_1 .' > '.$label_2.' > '.$label_3."\",\"".$label_3."\"\n";
                        continue;
                    }
                    foreach ($v3['children'] as $v4){
                        $label_4 = $v4['local_display_name'];
                        echo $v4['id'].',"'.$label_1 .' > '.$label_2.' > '.$label_3.' > '.$label_4."\",\"".$label_4."\"\n";
                        continue;
                    }
                }
            }
        }
        exit();
    }

    public function actionGetGlobalCategory(){
        $where = ['platform_type'=>Base::PLATFORM_TIKTOK];
        $shop = Shop::findOne($where);
        $api_service = FApiService::factory($shop);
        $json = $api_service->getGlobalCategory();
        //echo CommonUtil::jsonFormat($json);
        //$json = json_decode($result,true);
        $json = self::tree($json,0);
        foreach ($json as $v1){
            $label_1 = $v1['category_name'];
            if(empty($v1['children'])){
                echo $v1['id'].',"'.$label_1."\"".$label_1."\",\"".$label_1."\"\n";
                continue;
            }

            foreach ($v1['children'] as $v2){
                $label_2 = $v2['category_name'];
                if(empty($v2['children'])){
                    echo $v2['id'].',"'.$label_1 .' > '.$label_2."\",\"".$label_2."\"\n";
                    continue;
                }
                foreach ($v2['children'] as $v3){
                    $label_3 = $v3['category_name'];
                    if(empty($v3['children'])){
                        echo $v3['id'].',"'.$label_1 .' > '.$label_2.' > '.$label_3."\",\"".$label_3."\"\n";
                        continue;
                    }
                    foreach ($v3['children'] as $v4){
                        $label_4 = $v4['category_name'];
                        echo $v4['id'].',"'.$label_1 .' > '.$label_2.' > '.$label_3.' > '.$label_4."\",\"".$label_4."\"\n";
                        continue;
                    }
                }
            }
        }
        exit();
    }

    public static function tree($list,$pid = ''){
        $tree = array(); //每次都声明一个新数组用来放子元素
        foreach($list as $v){
            if($v['parent_id'] == $pid){ //匹配子记录
                $v['children'] = self::tree($list,$v['id']);//递归获取子记录
                if($v['children'] == null){
                    unset($v['children']);//如果子元素为空则unset()进行删除，说明已经到该分支的最后一个元素了（可选）
                }
                $tree[] = $v; //将记录存入新数组
            }
        }
        return $tree; //返回新数组
    }

}