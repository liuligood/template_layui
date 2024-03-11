<?php
namespace common\services\goods;

use common\models\goods\GoodsChild;
use common\models\goods\WordTranslate;
use yii\helpers\ArrayHelper;

class WordTranslateService
{

    /**
     * 获取翻译名称
     * @param $name
     * @param $language
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getTranslateName($name,$language)
    {
        $lists = WordTranslate::find()->where(['name'=>$name,'language'=>$language,'status'=>WordTranslate::STATUS_VALID])
            ->select('name,lname')->asArray()->all();
        return ArrayHelper::map($lists,'name','lname');
    }

    /**
     * 添加商品翻译
     * @param $goods_no
     * @return bool
     */
    public function addGoodsTranslate($goods_no)
    {
        $goods_child = GoodsChild::find()->where(['goods_no' => $goods_no])->all();
        $word = [];
        foreach ($goods_child as $v) {
            if (!empty($v['colour'])) {
                $word[] = $v['colour'];
            }

            if (!empty($v['size'])) {
                $word[] = $v['size'];
            }
        }
        $word = array_unique($word);
        return $this->addWord($word);
    }

    /**
     * 添加关键字
     * @param $word
     * @return bool
     */
    public function addWord($word)
    {
        $language = [
            'fr', 'ru', 'pl', 'es'
        ];
        $word_lists = WordTranslate::find()->where(['name' => $word, 'language' => $language])
            ->select('name,language')->asArray()->all();

        $data = [];
        foreach ($language as $l_v) {
            foreach ($word as $word_v) {
                $exist = false;
                if (!empty($word_lists)) {
                    foreach ($word_lists as $e_v) {
                        if ($e_v['name'] == $word_v && $e_v['language'] == $l_v) {
                            $exist = true;
                        }
                    }
                }
                if ($exist == true) {
                    continue;
                }
                $data[] = [
                    'name' => $word_v,
                    'language' => $l_v,
                    'status' => WordTranslate::STATUS_UNCONFIRMED,
                    'add_time' => time(),
                    'update_time' => time(),
                ];
            }
        }
        if (empty($data)) {
            return true;
        }

        $add_columns = [
            'name',
            'language',
            'status',
            'add_time',
            'update_time',
        ];
        return WordTranslate::getDb()->createCommand()->batchIgnoreInsert(WordTranslate::tableName(), $add_columns, $data)->execute();
    }

}