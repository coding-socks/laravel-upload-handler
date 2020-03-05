# Laravel Chunk Uploader

Chunk Uploader Package For Laravel

[![](https://travis-ci.org/LaraCrafts/laravel-chunk-uploader.svg?branch=master)](https://travis-ci.org/LaraCrafts/laravel-chunk-uploader)
[![](https://poser.pugx.org/laracrafts/laravel-chunk-uploader/downloads)](https://packagist.org/packages/laracrafts/laravel-chunk-uploader)
[![](https://poser.pugx.org/laracrafts/laravel-chunk-uploader/version)](https://packagist.org/packages/laracrafts/laravel-chunk-uploader)
[![](https://scrutinizer-ci.com/g/LaraCrafts/laravel-chunk-uploader/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/LaraCrafts/laravel-chunk-uploader/)
[![](https://poser.pugx.org/laracrafts/laravel-chunk-uploader/license)](https://packagist.org/packages/laracrafts/laravel-chunk-uploader)

This package helps integrate a Laravel application with chunk uploader libraries eg.
[DropzoneJS](https://www.dropzonejs.com/) and
[jQuery-File-Upload from blueimp](https://blueimp.github.io/jQuery-File-Upload/).

Uploading a large file in chunks can help reduce risks. 

- PHP from 5.3.4 limits the number of concurrent uploads and by uploading a file in one request can limit the
availability of a service. ([max_file_uploads][php-max-file-uploads])
- For security reasons the payload size and the uploadable file size is limited in many systems PHP is not an exception.
([upload_max_filesize][php-upload-max-filesize])
- It can be useful to check the meta information of a file and decline an upload upfront so the user does not have to
wait for minutes or seconds to upload a large file and then receive a message that the file type or mime type is not
allowed.
- Can include resume functionality which means an upload can be continued after a reconnection.

However, there is not a single RFC about chunked uploads and this caused many different implementations. The most mature
project at the moment is [tus](https://tus.io/).

- [Installation](#installation)
    - [Requirements](#requirements)
- [Usage](#usage)
    - [Events](#events)
    - [Changing the driver](#changing-the-driver)
    - [Adding your own drivers](#adding-your-own-drivers)
- [Drivers](#drivers)
    - [Monolith](#monolith-driver)
    - [Blueimp](#blueimp-driver)
    - [DropzoneJS](#dropzonejs-driver)
    - [Resumable.js](#resumable-js-driver)
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

- PHP 7.1 or higher
- Laravel 5.5 or higher

## Usage

1. Register a route
```php
Route::any('/my-route', 'MyController@myFunction');
```
2. Retrieve the upload handler. (The chunk upload handler can be retrieved from the container in two ways.)
  - Using dependency injection
```php
use Illuminate\Http\Request;
use LaraCrafts\ChunkUploader\UploadHandler;

class MyController extends Controller
{
    public function myFunction(Request $request, UploadHandler $handler)
    {
        return $handler->handle($request);
    }
}
```
  - Resolving from the app container
```php
use Illuminate\Http\Request;
use LaraCrafts\ChunkUploader\UploadHandler;

class MyController extends Controller
{
    public function myFunction(Request $request)
    {
        $handler = app()->make(UploadHandler::class);
        return $handler->handle($request);}
}
```

The handler exposes the following methods:

Method         | Description
---------------|--------------------------
`handle`       | Handle the given request

"Handle" is quite vague but there is a reason for that. This library tries to provide more functionality then just
saving the uploaded chunks. It is also adds functionality for resumable uploads which depending on the client side
library can be differ very much. Also, when possible the library gives the opportunity to download the uploaded file.

### Events

Once a file upload is finished a `\LaraCrafts\ChunkUploader\Event\FileUploaded` is triggered. This event contains
the disk and the path of the uploaded file.
[Registering Events & Listeners from Laravel](https://laravel.com/docs/5.8/events#registering-events-and-listeners)

You can also add a `Closure` as the second parameter of the `handle` method to add an inline listener. The listener
is called with the disk and the path of the uploaded file. 

```php
$handler->handle($request, function ($disk, $path) {
    // Triggered when upload is finished
});
```

### Changing the driver

You can change the default driver by setting a `UPLOAD_DRIVER` environment variable or publishing the
config file and changing it directly.

### Adding your own drivers

Much like Laravel's core components, you can add your own drivers for this package. You can do this by adding the
following code to a service provider.

```php
app()->make(UploadManager::class)->extend('my_driver', function () {
    return new MyCustomUploadDriver();
});
```

If you are adding a driver you need to extend the `\LaraCrafts\ChunkUploader\Driver\UploadDriver` abstract class, for
which you can use the shipped drivers (e.g. `\LaraCrafts\ChunkUploader\Driver\BlueimpUploadDriver`) as an example as to
how.

If you wrote a custom driver that others might find useful, please consider adding it to the package via a pull request.

## Drivers

Below is a list of available drivers along with their individual specs:

Service                              | Driver name    | Chunk upload | Resumable
-------------------------------------|----------------|--------------|-----------
[Monolith](#monolith-driver)         | `monolith`     | no           | no
[Blueimp](#blueimp-driver)           | `blueimp`      | yes          | yes
[DropzoneJS](#dropzonejs-driver)     | `dropzone`     | yes          | no
[Resumable.js](#resumable-js-driver) | `resumable-js` | yes          | yes

### Monolith driver

This driver is a fallback driver as it can handle normal file request. Save and delete capabilities are also added.

### Blueimp driver

[website](https://blueimp.github.io/jQuery-File-Upload/)

This driver handles requests made by the Blueimp jQuery File Upload client library.

### DropzoneJS driver

[website](https://www.dropzonejs.com/)

This driver handles requests made by the DropzoneJS client library.

### Resumable.js driver

[website](http://resumablejs.com/)

This driver handles requests made by the Resumable.js client library.

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

All contributions are welcomed for this project, please refer to the [CONTRIBUTING.md][contributing] file for more
information about contribution guidelines.

## License

**Copyright (c) 2019 LaraCrafts.**

This product is licensed under the MIT license, please refer to the [License file][license] for more information.

[contributing]: https://github.com/LaraCrafts/laravel-chunk-uploader/blob/master/CONTRIBUTING.md
[license]: https://github.com/LaraCrafts/laravel-chunk-uploader/blob/master/LICENSE
[php-max-file-uploads]: https://www.php.net/manual/en/ini.core.php#ini.max-file-uploads
[php-upload-max-filesize]: https://www.php.net/manual/en/ini.core.php#ini.upload-max-filesize
