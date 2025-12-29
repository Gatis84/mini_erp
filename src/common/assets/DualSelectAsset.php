<?php

namespace common\assets;

use yii\web\AssetBundle;

class DualSelectAsset extends AssetBundle
{
    public $sourcePath = '@common/web';
    public $js = [
        'js/dual-select.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
