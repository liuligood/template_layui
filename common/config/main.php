<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'charset' => 'utf-8',
    'language' => 'zh-CN',
    'timeZone' => 'Asia/Shanghai',
    'components' => [
        'cache' => [
            // 'class' => 'yii\caching\FileCache',
            'class' => 'yii\redis\Cache',
            'keyPrefix' => 'yiicache_',
        ],
        'oss' => [
            'class' => 'common\components\Oss',
        ],
        'adminCache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],
    ],
    //   'bootstrap' => ['debug'],
    'modules'=>[
        //'gii' => [
        //  'class' => 'yii\gii\Module',
        // 'allowedIPs' => ['*'] // 按需调整这里
        // ],
        // 'debug' => [
        //   'class' => 'yii\debug\Module',
        // 'allowedIPs' => ['*']
        //],
    ],
];
