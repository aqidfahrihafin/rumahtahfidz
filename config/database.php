<?php

return array(
    /* Gunakan "auto", "mysql", atau "sqlite". */
    'driver' => 'auto',

    'mysql' => array(
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'rumahtahfidz',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ),

    'sqlite' => array(
        'path' => __DIR__ . '/../storage/tahfidz.sqlite',
    ),
);
