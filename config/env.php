<?php
/**
 * Environment Configuration Loader
 * Loads environment variables from .env file
 */

if (!class_exists('EnvLoader')) {
    class EnvLoader {
        private static $loaded = false;

        public static function load($path = null) {
            if (self::$loaded) {
                return;
            }

            if ($path === null) {
                $path = dirname(__DIR__) . '/.env';
            }

            if (!file_exists($path)) {
                return;
            }

            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }

                // Parse key=value pairs
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remove quotes if present
                    $value = trim($value, '"\'');
                    
                    // Set as environment variable
                    if (!array_key_exists($key, $_ENV)) {
                        $_ENV[$key] = $value;
                        putenv("$key=$value");
                    }
                }
            }

            self::$loaded = true;
        }

        public static function get($key, $default = null) {
            self::load();
            return $_ENV[$key] ?? $default;
        }
    }

    // Auto-load environment variables
    EnvLoader::load();
}
