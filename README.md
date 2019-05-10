# Laravel Chunk Uploader

Chunk Uploader Package For Laravel

<p align="center">
    <a href="https://travis-ci.org/LaraCrafts/laravel-chunk-uploader"><img src="https://travis-ci.org/LaraCrafts/laravel-chunk-uploader.svg?branch=master"></a>
    <a href="https://packagist.org/packages/laracrafts/laravel-chunk-uploader"><img src="https://poser.pugx.org/laracrafts/laravel-chunk-uploader/downloads"></a>
    <a href="https://packagist.org/packages/laracrafts/laravel-chunk-uploader"><img src="https://poser.pugx.org/laracrafts/laravel-chunk-uploader/version"></a>
    <a href="https://scrutinizer-ci.com/g/LaraCrafts/laravel-chunk-uploader/"><img src="https://scrutinizer-ci.com/g/LaraCrafts/laravel-chunk-uploader/badges/coverage.png?b=master"></a>
    <a href="https://packagist.org/packages/laracrafts/laravel-chunk-uploader"><img src="https://poser.pugx.org/laracrafts/laravel-chunk-uploader/license"></a>
</p>

- [Installation](#installation)
    - [Requirements](#requirements)
- [Usage](#usage)
    - [Changing the driver](#changing-the-driver)
    - [Adding your own drivers](#adding-your-own-drivers)
- [Drivers](#drivers)
    - [Monolith](#monolith-driver)
    - [Blueimp](#blueimp-driver)
- [Identifiers](#identifiers)
    - [Session identifier](#session-identifier)
- [Contribution](#contribution)
- [License](#license)
    
## Installation

You can easily install this package using Composer, by running the following command:

```bash
composer require laracrafts/laravel-chunk-uploader
```

### Requirements

This package has the following requirements:

- PHP 7.0 or higher
- Laravel 5.5 or higher

## Usage

The chunk upload handler can be retrieved from the container in two ways:

- Using dependency injection
```php
class MyController extends Controller
{
    public function myFunction(Request $request, UploadHandler $handler)
    {
        $handler->handle($request);
    }
}
```
- Resolving from the app container
```php
$handler = app()->make(UploadHandler::class)
$handler->handle($request);
```

The handler exposes the following methods:

Method         | Description
---------------|-------------------------------------
`handle`       | Handle the given request

### Changing the driver

You can change the default driver by setting a `UPLOAD_DRIVER` environment variable or publishing the
config file and changing it directly.

### Adding your own drivers

Much like Laravels [core components][5], you can add your own drivers for this package. You can do this
by adding the following code to a central place in your application (preferably a service provider).

```php
app()->make(UploadManager::class)->extend('my_driver', function () {
    return new MyCustomUploadDriver();
});
```

If you are adding a driver you need to extend the `UploadDriver.php` abstract class, for which
you can use the shipped drivers (e.g. `MonolithUploadDriver`) as an example as to how.

If you wrote a custom driver that others might find useful, please consider adding it to the package via
a pull request.

## Drivers

Below is a list of available drivers along with their individual specs:

Service                      | Driver name | Chunk upload | Resumable
-----------------------------|-------------|--------------|-----------
[Monolith](#monolith-driver) | `monolith`  | no           | no
[Blueimp](#blueimp-driver)   | `blueimp`   | yes          | yes

### Monolith driver

This driver is a fallback driver as it can handle normal file request. Save and delete capabilities are also added.

### Blueimp driver

[website](https://blueimp.github.io/jQuery-File-Upload/)

This driver handles requests made by the Blueimp jQuery File Upload client library.

## Identifiers

In some cases an identifier is needed for the uploaded file when the client side library does not provide one.
This identifier is important for resumable uploads as the library has to be able to check the status of the given
file for a specific client. Without the identifier collisions can happen.

Service                                   | Driver name
------------------------------------------|-------------
[Session identifier](#session-identifier) | `session`

### Session identifier

This identifier uses the client session and the original file name to create an identifier for the upload file.

## Contribution

All contributions are welcomed for this project, please refer to the [CONTRIBUTING.md][1] file for more information about contribution guidelines.

## License

**Copyright (c) 2019 LaraCrafts.**

This product is licensed under the MIT license, please refer to the [License file][2] for more information.

[1]: https://github.com/LaraCrafts/laravel-chunk-uploader/blob/master/CONTRIBUTING.md
[2]: https://github.com/LaraCrafts/laravel-chunk-uploader/blob/master/LICENSE
[5]: https://laravel.com/docs/5.0/extending#managers-and-factories
