<?php

namespace Obelaw\Ium\Url\Providers;

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;
use Obelaw\Ium\ObelawiumManager;
use Obelaw\Ium\Url\Services\UrlService;

class URLServiceProvider extends ServiceProvider
{
    public function register()
    {
        ObelawiumManager::macro('url', function () {
            return new UrlService($this->config());
        });
    }

    public function boot()

    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

            AboutCommand::add('Obelawium', fn() => ['Obelawium URL' => '0.1.0']);
        }
    }
}
