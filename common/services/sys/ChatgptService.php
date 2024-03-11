<?php
namespace common\services\sys;

use common\extensions\openai\Chatgpt;
use common\models\sys\ChatgptTemplate;
use yii\helpers\ArrayHelper;

class ChatgptService
{

    /**
     * 获取执行模板
     * @param $chatgpt_template
     * @param $param
     * @return array|string|void
     */
    public static function getTemplateCon($chatgpt_template,$param = [])
    {
        $template_content = $chatgpt_template['template_content'];
        $replace = [];
        foreach ($param as $key => $val) {
            $replace["{{$key}}"] = $val;
        }
        switch ($chatgpt_template['template_type']){
            case ChatgptTemplate::TEMPLATE_TYPE_COMPLETIONS:
                return strtr($template_content, $replace);
            case ChatgptTemplate::TEMPLATE_TYPE_CHAT:
                $template_content = json_decode($template_content,true);
                $send_data = [];
                foreach ($template_content as $con_v){
                    $con_v['content'] = strtr($con_v['content'], $replace);
                    $send_data[] = $con_v;
                }
                return $send_data;
        }
    }

    /**
     * 模板执行
     * @param $code
     * @param $param
     * @param $test_id
     * @return array|false|string
     * @throws \Exception
     */
    public static function templateExec($code,$param = [],$test_id = '')
    {
        $chatgpt_template = ChatgptTemplate::find()->where(['template_code' => $code,'status' => ChatgptTemplate::STATUS_NORMAL])->one();
        if (!empty($test_id)) {
            $chatgpt_template = ChatgptTemplate::find()->where(['id' => $test_id])->one();
        }
        $gpt_params = empty($chatgpt_template['param'])?[]:json_decode($chatgpt_template['param'],true);
        $gpt_params = ArrayHelper::map($gpt_params,'name','content');
        $chatgpt = new Chatgpt();
        $send_data = self::getTemplateCon($chatgpt_template, $param);
        switch ($chatgpt_template['template_type']) {
            case ChatgptTemplate::TEMPLATE_TYPE_COMPLETIONS:
                $result = $chatgpt->completions($send_data,$gpt_params);
                break;
            case ChatgptTemplate::TEMPLATE_TYPE_CHAT:
                $result = $chatgpt->chat($send_data,$gpt_params);
                break;
        }

        //处理返回值
        if (!empty($result)) {
            if (is_array($result)) {
                $result = array_map(function ($item) use ($code) {
                    return self::dealItem($code, $item);
                }, $result);
            } else {
                $result = self::dealItem($code, $result);
            }
            return $result;
        }
        return false;
    }

    /**
     *
     * @param $item
     * @param $code
     * @return mixed|string|string[]
     */
    public static function dealItem($code,$item)
    {
        if (in_array($code, ['goods_name', 'goods_name_cn','ozon_goods_name','allegro_goods_name'])) {
            $item = str_replace(["w/ ", "w/", '|'], ["with ","with ",","], $item);
        }
        if (in_array($code, ['ozon_claim_goods_name', 'allegro_claim_goods_name','ozon_goods_name','allegro_goods_name'])) {
            $item = rtrim($item, '.');
        }
        if (in_array($code, ['goods_desc'])) {//去除前面序号
            /*$result = [];
            $str_arr = explode(PHP_EOL, $item);
            foreach ($str_arr as $v) {
                $v = trim($v);
                if (empty($v)) {
                    continue;
                }
                $v = preg_replace('/^\d./', '', $v);
                $v = ltrim($v, '-');
                $v = trim($v);
                $result[] = $v;
            }
            $item = implode(PHP_EOL,$result);*/
        }
        return $item;
    }

}