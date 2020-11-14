<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Upload Driver
    |--------------------------------------------------------------------------
    |
    | The module supports several "handle" function as drivers for the
    | handling of uploaded file. You may specify which one you're using
    | throughout your application here. By default, the module is setup for
    | monolith upload.
    |
    | Supported: "monolith", "blueimp", "dropzone", "flow-js",
    | "ng-file-upload", "resumable-js", "simple-uploader-js"
    |
    */

    'handler' => env('UPLOAD_HANDLER', 'monolith'),

    /*
    |--------------------------------------------------------------------------
    | Client Identifier
    |--------------------------------------------------------------------------
    |
    | The module can support several identifiers to identify a client. You may
    | specify which one you're using throughout your application here. By
    | default, the module is setup for session identity.
    |
    | Supported: "auth", "nop", "session"
    |
     */

    'identifier' => 'session',

    /*
    |--------------------------------------------------------------------------
    | Cleanup
    |--------------------------------------------------------------------------
    |
    | Here you may enable or disable the deletion of chunks after merging
    | them.
    |
    */

    'sweep' => true,

    /*
    |--------------------------------------------------------------------------
    | Upload Disk
    |--------------------------------------------------------------------------
    |
    | Here you may configure the target disk for chunk and merged files.
    |
    */

    'disk' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Upload Disk
    |--------------------------------------------------------------------------
    |
    | Here you may configure the target directory for chunk and merged files.
    |
    */

    'directories' => [

        'chunk' => 'chunks',

        'merged' => 'merged',

    ],

    /*
    |--------------------------------------------------------------------------
    | Monolith Options
    |--------------------------------------------------------------------------
    |
    | Here you may configure the options for the monolith driver.
    |
    */

    'monolith' => [

        'param' => 'file',

    ],

    /*
    |--------------------------------------------------------------------------
    | Blueimp Options
    |--------------------------------------------------------------------------
    |
    | Here you may configure the options for the blueimp driver.
    |
    */

    'blueimp' => [

        'param' => 'file',

    ],

    /*
    |--------------------------------------------------------------------------
    | Dropzone Options
    |--------------------------------------------------------------------------
    |
    | Here you may configure the options for the Dropzone driver.
    |
    */

    'dropzone' => [

        'param' => 'file',

    ],

    /*
    |--------------------------------------------------------------------------
    | Flow.js Options
    |--------------------------------------------------------------------------
    |
    | Here you may configure the options for the Flow.js driver.
    |
    */

    'flow-js' => [

        // The name of the multipart request parameter to use for the file chunk
        'param' => 'file',

        //  HTTP method for chunk test request.
        'test-method' => Illuminate\Http\Request::METHOD_GET,
        //  HTTP method to use when sending chunks to the server (POST, PUT, PATCH).
        'upload-method' => Illuminate\Http\Request::METHOD_POST,

    ],

    /*
    |--------------------------------------------------------------------------
    | Resumable.js Options
    |--------------------------------------------------------------------------
    |
    | Here you may configure the options for the Resumable.js driver.
    |
    */

    'resumable-js' => [

        // The name of the multipart request parameter to use for the file chunk
        'param' => 'file',

        //  HTTP method for chunk test request.
        'test-method' => Illuminate\Http\Request::METHOD_GET,
        //  HTTP method to use when sending chunks to the server (POST, PUT, PATCH).
        'upload-method' => Illuminate\Http\Request::METHOD_POST,

        // Extra prefix added before the name of each parameter included in the multipart POST or in the test GET.
        'parameter-namespace' => '',

        'parameter-names' => [
            // The name of the chunk index (base-1) in the current upload POST parameter to use for the file chunk.
            'chunk-number' => 'resumableChunkNumber',
            // The name of the total number of chunks POST parameter to use for the file chunk.
            'total-chunks' => 'resumableTotalChunks',
            // The name of the general chunk size POST parameter to use for the file chunk.
            'chunk-size' => 'resumableChunkSize',
            // The name of the total file size number POST parameter to use for the file chunk.
            'total-size' => 'resumableTotalSize',
            // The name of the unique identifier POST parameter to use for the file chunk.
            'identifier' => 'resumableIdentifier',
            // The name of the original file name POST parameter to use for the file chunk.
            'file-name' => 'resumableFilename',
            // The name of the file's relative path POST parameter to use for the file chunk.
            'relative-path' => 'resumableRelativePath',
            // The name of the current chunk size POST parameter to use for the file chunk.
            'current-chunk-size' => 'resumableCurrentChunkSize',
            // The name of the file type POST parameter to use for the file chunk.
            'type' => 'resumableType',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | simple-uploader.js Options
    |--------------------------------------------------------------------------
    |
    | Here you may configure the options for the simple-uploader.js driver.
    |
    */

    'simple-uploader-js' => [

        // The name of the multipart request parameter to use for the file chunk
        'param' => 'file',

        //  HTTP method for chunk test request.
        'test-method' => Illuminate\Http\Request::METHOD_GET,
        //  HTTP method to use when sending chunks to the server (POST, PUT, PATCH).
        'upload-method' => Illuminate\Http\Request::METHOD_POST,

    ],

];
