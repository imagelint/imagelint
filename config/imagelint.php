<?php

return [
    // This must be a local filesystem disk
    'cache_disk' => env('IMAGELINT_CACHE_DISK', 'local'),

    // This must be a local filesystem disk
    'tmp_disk' => env('IMAGELINT_tmp_DISK', 'local'),

    // This can be any disk
    // The final files will be stored here
    'compressed_disk' => env('IMAGELINT_COMPRESSED_DISK', 'local'),
];
