<?php

namespace LaraCrafts\ChunkUploader;

use Illuminate\Support\ServiceProvider;

class ChunkUploaderServiceProvider extends ServiceProvider
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
        $source = realpath(__DIR__ . '/../config/chunk-uploader.php');
        $this->publishes([$source => config_path('chunk-uploader.php')]);

        $this->mergeConfigFrom($source, 'chunk-uploader');
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerChunkUploader();
    }

    /**
     * Register the Upload Handler instance.
     *
     * @return void
     */
    protected function registerChunkUploader()
    {
        $this->registerUploadManager();
        $this->registerIdentityManager();

        $this->app->singleton(UploadHandler::class, function () {
            /** @var \Illuminate\Support\Manager $uploadManager */
            $uploadManager = $this->app['chunk-uploader.upload-manager'];
            /** @var \Illuminate\Support\Manager $identityManager */
            $identityManager = $this->app['chunk-uploader.identity-manager'];

            $config = $this->app->make('config')->get('chunk-uploader');

            return new UploadHandler($uploadManager->driver(), $identityManager->driver(), $config);
        });
    }

    /**
     * Register the Upload Manager instance.
     *
     * @return void
     */
    protected function registerUploadManager()
    {
        $this->app->singleton('chunk-uploader.upload-manager', function () {
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
        $this->app->singleton('chunk-uploader.identity-manager', function () {
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
            'chunk-uploader.upload-manager',
        ];
    }
}
