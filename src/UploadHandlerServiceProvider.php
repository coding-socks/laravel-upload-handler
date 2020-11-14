<?php

namespace CodingSocks\UploadHandler;

use Illuminate\Support\ServiceProvider;

class UploadHandlerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig();
    }

    /**
     * Setup the config.
     *
     * @return void
     */
    protected function setupConfig()
    {
        $source = realpath(__DIR__ . '/../config/upload-handler.php');
        $this->publishes([$source => config_path('upload-handler.php')]);

        $this->mergeConfigFrom($source, 'upload-handler');
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerUploadHandler();
    }

    /**
     * Register the Upload Handler instance.
     *
     * @return void
     */
    protected function registerUploadHandler()
    {
        $this->registerUploadManager();
        $this->registerIdentityManager();

        $this->app->singleton(UploadHandler::class, function () {
            /** @var \Illuminate\Support\Manager $uploadManager */
            $uploadManager = $this->app['upload-handler.upload-manager'];

            $storageConfig = new StorageConfig($this->app->make('config')->get('upload-handler'));

            return new UploadHandler($uploadManager->driver(), $storageConfig);
        });
    }

    /**
     * Register the Upload Manager instance.
     *
     * @return void
     */
    protected function registerUploadManager()
    {
        $this->app->singleton('upload-handler.upload-manager', function () {
            return new UploadManager($this->app);
        });
    }

    /**
     * Register the Upload Manager instance.
     *
     * @return void
     */
    protected function registerIdentityManager()
    {
        $this->app->singleton('upload-handler.identity-manager', function () {
            return new IdentityManager($this->app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            UploadHandler::class,
            'upload-handler.upload-manager',
        ];
    }
}
