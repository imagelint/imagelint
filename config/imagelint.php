<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Disks
    |--------------------------------------------------------------------------
    |
    | These disks are used to store images. They should be set to one of the
    | disks that are defined in /config/filesystems.php
    |
    */

    // This disk is used to store uploaded images
    // ***This must be a disk which returns a url which is publicly accessible***
    'upload_disk' => env('IMAGELINT_UPLOAD_DISK', 'public'),

    // It's used as temporary storage for transforming and compressing the images
    // ***This must be a local filesystem disk***
    'tmp_disk' => env('IMAGELINT_TMP_DISK', 'local'),

    // This can be any disk
    // The final files will be stored here
    'output_cache_disk' => env('IMAGELINT_OUTPUT_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connection
    |--------------------------------------------------------------------------
    |
    | Here you may configure a queue connection which is used to compress
    | the images. If you specify no queue connection, the compression
    | will run, after the initial image is delivered to the user.
    |
    */
    'queue_connection' => env('IMAGELINT_QUEUE_CONNECTION', ''),
];
