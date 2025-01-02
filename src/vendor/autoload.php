<?php
// src/vendor/autoload.php

spl_autoload_register(function ($class) {
    $base_dir = __DIR__ . '/';

    // Mapping für OTPHP
    if (strpos($class, 'OTPHP\\') === 0) {
        $file = $base_dir . 'otphp/src/' . str_replace('\\', '/', substr($class, 6)) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }

    // Mapping für chillerlan\QRCode
    if (strpos($class, 'chillerlan\\QRCode\\') === 0) {
        $file = $base_dir . 'php-qrcode/src/' . str_replace('\\', '/', substr($class, 18)) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});