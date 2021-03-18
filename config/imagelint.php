<?php

return [
    // This must be a local filesystem disk
    'tmp_disk' => env('IMAGELINT_TMP_DISK', 'local'),

    // This can be any disk
    // The final files will be stored here
    'output_cache_disk' => env('IMAGELINT_OUTPUT_DISK', 'local'),
];
