<?php

namespace ColinDorr\BetterUpdates\resources\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class PluginAsset extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = __DIR__ . '/../';

        $this->css = [
            'css/settings.css',
            'css/toast.css',
        ];

        $this->js = [
            'js/toast.js',
        ];

        parent::init();
    }
}