<?php
return [
    'driver' => env('LOG_DRIVER', 'db'),
    'table' => env('LOG_TABLE', 'log_action'),
];
