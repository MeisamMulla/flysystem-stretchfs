<?php
namespace MeisamMulla\FlysystemStretchfs;

use Storage;
use League\Flysystem\Filesystem;
use MeisamMulla\SfsClient\StretchFS;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider {
    public function boot() {
        Storage::extend('stretchfs', function ($app, $config) {
            $client = new StretchFS([
                'domain' => $config['domain'],
                'token' => $config['token'],
            ]);
            
            $adapter = new StretchFsAdapter($client);

            return new Filesystem($adapter);
        });
    }
}
