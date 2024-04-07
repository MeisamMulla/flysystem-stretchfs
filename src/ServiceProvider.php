<?php
namespace MeisamMulla\FlysystemStretchfs;

use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use MeisamMulla\SfsClient\StretchFS;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Contracts\Foundation\Application;

class ServiceProvider extends BaseServiceProvider {
    public function boot() {
        Storage::extend('stretchfs', function ($app, $config) {
            $client = new StretchFS([
                'domain' => $config['domain'],
                'token' => $config['token'],
            ]);
            
            $adapter = new StretchFsAdapter($client);

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }
}
