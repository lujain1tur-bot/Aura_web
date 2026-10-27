<?php

return [
    'paths' => [resource_path('views')],

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        env('APP_STORAGE', storage_path()) . '/framework/views'
    ),
];
