<?php

return [
    'token' => env('PROPERTY_IMPORT_TOKEN'),
    'image_hosts' => array_filter(array_map('trim', explode(',', env('PROPERTY_IMPORT_IMAGE_HOSTS', 'imgar.zonapropcdn.com')))),
    'max_image_bytes' => 15 * 1024 * 1024,
];
