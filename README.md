# Flysystem Adapter for StretchFS

This package provides a [League\Flysystem](https://flysystem.thephpleague.com/) adapter for [StretchFS](https://github.com/nullivex/stretchfs-sdk). It allows you to use the Flysystem API to interact with your StretchFS storage system, leveraging the features and ease of use of Flysystem for file operations within StretchFS.

## Installation

You can install the package via composer:

```bash
composer require meisam-mulla/flysystem-stretchfs
```

## Configuration
After installation, you can configure the adapter in your Laravel application or in any PHP project that uses Flysystem. Here's how you can do it:

### Laravel
In your config/filesystems.php, add a new disk:

```php
'disks' => [
    // Other disks...
    
    'stretchfs' => [
        'driver' => 'stretchfs',
        'domain' => 'your_sfsserver.net',
        'token' => 'your_token',
    ],
],
```

### Plain PHP
If you're using Flysystem in a non-Laravel PHP project, you can directly instantiate the StretchFsAdapter:

```php
use League\Flysystem\Filesystem;
use MeisamMulla\SfsClient\StretchFS;
use MeisamMulla\FlysystemStretchfs\StretchFsAdapter;

$client = new StretchFS([
    'domain' => 'sfsserver.net',
    'token' => 'your_token',
]);

$adapter = new StretchFsAdapter($client);
$filesystem = new Filesystem($adapter);
```
