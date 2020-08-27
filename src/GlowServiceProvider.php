<?php

namespace Bavix\Flysystem\Glow;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;

class GlowServiceProvider extends ServiceProvider
{

    /**
     * @return void
     */
    public function boot(): void
    {
        Storage::extend('glow', static function ($app, $config) {
            return new Filesystem(new GlowAdapter(new Config($config)));
        });
    }

}
