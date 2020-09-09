<?php

namespace Bavix\Flysystem\Glow;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;

class GlowServiceProvider extends ServiceProvider
{

    /**
     * @return void
     */
    public function boot(): void
    {
        Storage::extend('glow', static function ($app, $config) {
            $adapter = new GlowAdapter($config);
            return new Filesystem($adapter, $config);
        });
    }

}
