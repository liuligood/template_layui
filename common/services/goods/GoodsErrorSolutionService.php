<?php

namespace common\services\goods;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\goods_shop\GoodsErrorAssociation;
use common\models\goods_shop\GoodsErrorSolution;
use common\services\cache\FunCacheService;

class GoodsErrorSolutionService
{

    /**
     * 获取列表
     * @param $platform_type
     * @return array
     */
    public static function getLists($platform_type)
    {
        return FunCacheService::set(['goods_error_solution', [$platform_type]], function () use ($platform_type) {
            return \common\models\goods_shop\GoodsErrorSolution::find()->where(['platform_type' => $platform_type])->asArray()->all();
        }, 60 * 60);
    }

    /**
     * 清除缓存
     * @param $platform_type
     * @return void
     */
    public static function clearListsCache($platform_type)
    {
        FunCacheService::clearOne(['goods_error_solution', [$platform_type]]);
    }

    /**
     * @param $arr
     * @param $platform_type
     * @return mixed|void
     */
    public function getErrorSolution($arr,$platform_type = Base::PLATFORM_OZON)
    {
        if(empty($arr)) {
            return false;
        }

        if (!is_array($arr)) {
            $description = $arr;
        } else {
            if ($platform_type == Base::PLATFORM_OZON) {
                if (empty($arr['description'])) {
                    return false;
                }
                $description = $arr['description'] . (empty($arr['message']) ? '' : ('. ' . $arr['message']));
            }
            if ($platform_type == Base::PLATFORM_ALLEGRO) {
                if (empty($arr['userMessage'])) {
                    return false;
                }
                $description = $arr['userMessage'] . (empty($arr['path']) ? '' : (':' . $arr['path']));
            }
        }
        $goods_errors = self::getLists($platform_type);
        foreach ($goods_errors as $error_v) {
            if (CommonUtil::compareStrings($error_v['error_message'], $description)) {
                return $error_v['id'];
            }
        }
        $goods_error_solution = new GoodsErrorSolution();
        $goods_error_solution->platform_type = $platform_type;
        $goods_error_solution->error_message = $description;
        $goods_error_solution->param = json_encode($arr, JSON_UNESCAPED_UNICODE);
        $goods_error_solution->save();
        self::clearListsCache($platform_type);
        return $goods_error_solution['id'];
    }

    /**
     * 添加错误
     * @param $goods_shop_id
     * @param $error_arr
     * @return bool|void
     */
    public function addError($platform_type, $goods_shop_id, $error_arr)
    {
        if (empty($goods_shop_id)) {
            return false;
        }

        GoodsErrorAssociation::deleteAll(['goods_shop_id' => $goods_shop_id]);
        if (empty($error_arr)) {
            return true;
        }
        $error_arr = (array)$error_arr;
        foreach ($error_arr as $arr) {
            $goods_error_solution_id = $this->getErrorSolution($arr, $platform_type);
            if (empty($goods_error_solution_id)) {
                continue;
            }
            $goods_error_association = new GoodsErrorAssociation();
            $goods_error_association->goods_shop_id = $goods_shop_id;
            $goods_error_association->error_id = $goods_error_solution_id;
            $goods_error_association->save();
        }
    }

    /**
     * 显示错误
     * @param $goods_shop_id
     * @param $platform_type
     * @return void
     */
    public function showError($goods_shop_id,$platform_type)
    {
        $goods_error_ids = GoodsErrorAssociation::find()->where(['goods_shop_id' => $goods_shop_id])->select('error_id')->asArray()->column();
        $error_lists = self::getLists($platform_type);
        $error = [];
        foreach ($error_lists as $error_v) {
            if(in_array($error_v['id'],$goods_error_ids)) {
                $error[] = $error_v;
            }
        }
        return $error;
    }

}