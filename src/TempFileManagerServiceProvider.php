<?php

namespace Vldmir\TempFileManager;

use Illuminate\Support\ServiceProvider;

class TempFileManagerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/temp-file-manager.php' => config_path('temp-file-manager.php'),
        ], 'config');

        $this->mergeConfigFrom(
            __DIR__.'/../config/temp-file-manager.php', 'temp-file-manager'
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\CleanupTempFilesCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->app->singleton(TempFileManager::class, function ($app) {
            return new TempFileManager(config('temp-file-manager'));
        });

        $this->app->alias(TempFileManager::class, 'temp-manager');
    }
}
