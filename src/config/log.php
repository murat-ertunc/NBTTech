<?php
return [
    'driver' => env('LOG_DRIVER', 'db'),
    'table' => env('LOG_TABLE', 'log_action'),
    'file_path' => env('LOG_FILE_PATH', STORAGE_PATH . 'logs'),
];
