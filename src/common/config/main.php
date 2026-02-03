<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@common' => dirname(dirname(__DIR__)) . '/common',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'formatter' => [
            'class' => yii\i18n\Formatter::class,
            'datetimeFormat' => 'php:Y-m-d H:i',
        ],
    ],
];
