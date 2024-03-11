<?php
namespace backend\assets;

use yii\web\AssetBundle;

class CategoryJsAsset extends AssetBundle
{
    public $js = [
        'assets/js/category.js',
    ];

    public function init()
    {
        parent::init();

        $js_file_time_key = 'com::category::js_file_time';
        $js_file_time = \Yii::$app->redis->get($js_file_time_key);

        // 引用动态生成的JS脚本
        $this->js[0] .= '?' . $js_file_time;
    }

}