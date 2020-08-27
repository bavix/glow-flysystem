# Glow Flysystem Adapter

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bavix/glow-flysystem/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/bavix/glow-flysystem/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/bavix/glow-flysystem/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/bavix/glow-flysystem/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/bavix/glow-flysystem/badges/build.png?b=master)](https://scrutinizer-ci.com/g/bavix/glow-flysystem/build-status/master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/bavix/glow-flysystem/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

[![Package Rank](https://phppackages.org/p/bavix/glow-flysystem/badge/rank.svg)](https://packagist.org/packages/bavix/glow-flysystem)
[![Latest Stable Version](https://poser.pugx.org/bavix/glow-flysystem/v/stable)](https://packagist.org/packages/bavix/glow-flysystem)
[![Latest Unstable Version](https://poser.pugx.org/bavix/glow-flysystem/v/unstable)](https://packagist.org/packages/bavix/glow-flysystem)
[![License](https://poser.pugx.org/bavix/glow-flysystem/license)](https://packagist.org/packages/bavix/glow-flysystem)
[![composer.lock](https://poser.pugx.org/bavix/glow-flysystem/composerlock)](https://packagist.org/packages/bavix/glow-flysystem)

Glow Flysystem Adapter - Easy work with Glow CDN API.

* **Vendor**: bavix
* **Package**: Glow Flysystem
* **Version**: [![Latest Stable Version](https://poser.pugx.org/bavix/glow-flysystem/v/stable)](https://packagist.org/packages/bavix/glow-flysystem)
* **Laravel Version**: `7.x`
* **PHP Version**: 7.3+ 
* **[Composer](https://getcomposer.org/):** `composer require bavix/glow-flysystem`

### Usage

Add disk in config `config/filesystems.php`.
```php
    'disks' => [
        'glow' => [
            'driver' => 'glow',
            'bucket' => env('GLOW_BUCKET'),
            'url' => env('GLOW_URL'),
            'endpoint' => env('GLOW_ENDPOINT'),
            'token' => env('GLOW_TOKEN'),
            'visibility' => 'public',
        ],
    ],
```

Usage example.
```php
use Illuminate\Support\Facades\Storage;

$glow = Storage::disk('glow');
$glow->put('glow.txt', 'Hello, flysystem!');
var_dump($glow->url('glow.txt')); // URL for download file
var_dump($glow->delete('glow.txt'));
```

---
Supported by

[![Supported by JetBrains](https://cdn.rawgit.com/bavix/development-through/46475b4b/jetbrains.svg)](https://www.jetbrains.com/)
