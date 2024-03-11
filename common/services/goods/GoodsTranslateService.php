<?php
namespace common\services\goods;

use common\models\goods\GoodsTranslate;
use common\models\goods\GoodsTranslateExec;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\web\Response;

class GoodsTranslateService
{

    /**
     * 所有语言
     * @var string[]
     */
    public $all_language = [
        'es','fr','pl','ru','en','it'
    ];

    public $language = null;

    public function __construct($language)
    {
        $this->language = $language;
    }

    /**
     * 设置语言
     * @param $language
     */
    public function setLanguage($language){
        $this->language = $language;
    }

    /**
     * 获取model
     * @return \common\models\goods\GoodsTranslate
     * @throws Exception
     */
    public function getModel()
    {
        $language = $this->language;
        $goods_translate_class = 'common\models\goods\goods_translate\GoodsTranslate' . ucfirst($language);
        if (class_exists($goods_translate_class)) {
            return new $goods_translate_class();
        }
        throw new Exception("找不到GoodsTranslate类".$goods_translate_class, 8900);
    }

    /**
     * 获取单个商品信息
     * @param $goods_no
     * @return array
     * @throws Exception
     */
    public function getGoodsInfo($goods_no,$goods_field = null,$status = null)
    {
        $where = ['goods_no' => $goods_no];
        if(!empty($goods_field)) {
            $where['goods_field'] = $goods_field;
        }
        if(!is_null($status)) {
            $where['status'] = $status;
        }
        $lists = $this->getModel()->find()->where($where)->asArray()->all();
        return ArrayHelper::map($lists, 'goods_field', 'content');
    }

    /**
     * 添加商品信息
     * @param $goods_no
     * @param $goods_field
     * @param $content
     * @param $md5_content
     * @param $status
     * @return bool
     * @throws Exception
     */
    public function addGoodsInfo($goods_no, $goods_field, $content, $md5_content = '', $status = GoodsTranslate::STATUS_CONFIRMED)
    {
        $model = $this->getModel();
        $model['goods_no'] = $goods_no;
        $model['goods_field'] = $goods_field;
        $model['content'] = $content;
        $model['md5_content'] = $md5_content;
        $model['status'] = $status;
        return $model->save();
    }

    /**
     * 修改商品信息
     * @param $goods_no
     * @param $goods_field
     * @param $content
     * @param $md5_content
     * @param $status
     * @return bool
     * @throws Exception
     */
    public function updateGoodsInfo($goods_no, $goods_field, $content, $md5_content = '', $status = GoodsTranslate::STATUS_CONFIRMED)
    {
        $goods_info = self::getModel()->find()->where(['goods_no' => $goods_no, 'goods_field' => $goods_field,'status'=>$status])->limit(1)->one();
        if (empty($goods_info)) {
            return $this->addGoodsInfo($goods_no, $goods_field, $content, $md5_content, $status);
        } else {
            $goods_info['content'] = $content;
            $goods_info['md5_content'] = $md5_content;
            return $goods_info->save();
        }
    }


    /**
     * 校验商品信息
     * @param $goods_no
     * @param $goods_field
     * @param $md5_content
     * @return bool
     * @throws Exception
     */
    public function checkGoodsInfo($goods_no, $goods_field, $md5_content)
    {
        $goods_info = self::getModel()->find()->where(['goods_no' => $goods_no, 'goods_field' => $goods_field])->orderBy('status desc')->limit(1)->one();
        if (!empty($goods_info)) {
            if ($goods_info['status'] == GoodsTranslate::STATUS_MULTILINGUAL) {
                return $goods_info['content'];
            }
            if ($goods_info['md5_content'] == $md5_content) {
                return $goods_info['content'];
            }
        }
        return false;
    }

    /**
     * 准备重新翻译
     * @param $goods_no
     * @param $goods_field
     * @return int
     * @throws Exception
     */
    public function readyToRetranslate($goods_no,$goods_field)
    {
        return self::getModel()->updateAll(['status'=>GoodsTranslate::STATUS_UNCONFIRMED],['goods_no' => $goods_no, 'goods_field' => $goods_field]);
    }

    /**
     * 添加翻译执行
     * @param $data
     * @return bool|void
     * @throws Exception
     */
    public static function addTranslateExec($data)
    {
        $where = ['goods_no' => $data['goods_no'], 'language' => $data['language']];
        if(!empty($data['country_code'])) {
            $where['country_code'] = $data['country_code'];
        }
        if(!empty($data['platform_type'])) {
            $where['platform_type'] = $data['platform_type'];
        }
        $where['status'] = [GoodsService::PLATFORM_GOODS_STATUS_UNCONFIRMED, GoodsService::PLATFORM_GOODS_STATUS_TRANSLATE_FAIL];
        $exist = GoodsTranslateExec::find()->where($where)->one();
        if ($exist) {
            return true;
        }
        $data['status'] = GoodsService::PLATFORM_GOODS_STATUS_UNCONFIRMED;
        return GoodsTranslateExec::add($data);
    }

    /**
     * 删除商品信息
     * @param $goods_no
     * @param $goods_field
     * @param $status
     * @return bool
     * @throws Exception
     */
    public function deleteGoodsInfo($goods_no, $goods_field, $status)
    {
        $goods_info = self::getModel()->find()->where(['goods_no' => $goods_no, 'goods_field' => $goods_field, 'status' => $status])->limit(1)->one();
        if (!empty($goods_info)) {
            $goods_info->delete();
        }
        return true;
    }

    /**
     * 获取商品信息
     * @param $goods_no
     * @param $goods_field
     * @param $status
     * @return bool
     * @throws Exception
     */
    public function getMultilingualGoodsInfo($goods_no, $goods_field,$status = null)
    {
        $goods_info = self::getModel()->find()->where(['goods_no' => $goods_no, 'goods_field' => $goods_field, 'status' => $status])->limit(1)->one();
        return $goods_info;
    }

}