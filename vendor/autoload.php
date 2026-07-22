<?php
/**
 * Vendor Autoloader
 */

spl_autoload_register(function ($class) {
    // PHPMailer autoloader fallback
    if (strpos($class, 'PHPMailer\\PHPMailer\\') === 0) {
        $relative = substr($class, 20);
        $file = __DIR__ . '/phpmailer/src/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});
