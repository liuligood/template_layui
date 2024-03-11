<?php
namespace common\services\sys;


use backend\models\AuthAssignment;
use backend\models\AuthItemChild;
use common\services\cache\StaticCacheService;
use Yii;

class AccessService
{

    /**
     * 检验权限
     * @param string $permission_name 权限名称
     * @return mixed
     */
    public static function checkAccess($permission_name){
        return StaticCacheService::cacheData([__METHOD__,[md5($permission_name)]],function () use ($permission_name){
            return Yii::$app->authManager->checkAccess(Yii::$app->user->id, $permission_name) ? true : false;
        });
    }

    /**
     * 是否有所有商品数据认领权限
     * @return bool
     */
    public static function hasAllGoodsClaim()
    {
        return self::checkAccess('所有商品数据认领');
    }
    
    /**
     * 是否有所有商品权限
     * @return bool
     */
    public static function hasAllGoods()
    {
        return self::checkAccess('所有商品数据');
    }

    /**
     * 是否有商品数据归属权限(用于数据上传)
     * @return bool
     */
    public static function hasOwnerGoods()
    {
        return self::checkAccess('商品数据归属');
    }

    /**
     * 是否有所有商品权限
     * @return bool
     */
    public static function hasAllShop()
    {
        return self::checkAccess('所有店铺数据');
    }

    /**
     * 是否有所有负责人权限
     * @return bool
     */
    public static function hasAllUser()
    {
        return self::checkAccess('所有负责人数据');
    }

    /**
     * 是否有导出权限
     * @return bool
     */
    public static function hasExport()
    {
        return self::checkAccess('数据导出');
    }

    /**
     * 是否有财务金额
     * @return bool
     */
    public static function hasAmount()
    {
        return self::checkAccess('财务金额');

    }

    /**
     * 是否有所有采购商品权限
     * @return bool
     */
    public static function hasAllPurchaseGoods()
    {
        return self::checkAccess('所有采购商品数据');
    }

    /**
     * 获取平台商品完善组用户id
     * @return array
     */
    public static function getGoodsSupplementUserIds()
    {
        return StaticCacheService::cacheData(__METHOD__,function (){
            return \Yii::$app->authManager->getUserIdsByRole('平台商品完善组');
        });
    }

    /**
     * 获取采购商品组用户id
     * @return array
     */
    public static function getPurchaseUserIds()
    {
        return StaticCacheService::cacheData(__METHOD__,function (){
            $auth_name = '采购管理';
            $role_name = AuthItemChild::find()->where(['child'=>$auth_name])->select(['parent'])->column();
            $role_name = array_merge($role_name,[$auth_name]);
            return AuthAssignment::find()->where(['item_name'=>$role_name])->select(['user_id'])->column();
            //return \Yii::$app->authManager->getUserIdsByRole('采购商品组');
        });
    }

    /**
     * 获取异常处理组组用户id
     * @return array
     */
    public static function getOrderAbnormalUserIds()
    {
        return StaticCacheService::cacheData(__METHOD__,function (){
            return \Yii::$app->authManager->getUserIdsByRole('订单异常处理组');
        });
    }

    /**
     * 获取店铺运营组用户id
     * @return array
     */
    public static function getShopOperationUserIds()
    {
        return StaticCacheService::cacheData(__METHOD__,function (){
            return \Yii::$app->authManager->getUserIdsByRole('店铺运营组');
        });
    }

}