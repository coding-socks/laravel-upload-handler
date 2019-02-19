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
    | Supported: "monolith", "blueimp"
    |
    */

    'uploader' => env('UPLOAD_DRIVER', 'monolith'),

    /*
    |--------------------------------------------------------------------------
    | Client Identifier
    |--------------------------------------------------------------------------
    |
    | The module can support several identifiers to identify a client. You may
    | specify which one you're using throughout your application here. By
    | default, the module is setup for session identity.
    |
    | Supported: "session"
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

];
