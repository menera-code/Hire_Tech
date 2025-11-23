<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class EnvConfig {
    public static function load() {
        $envPath = dirname(__DIR__) . '/../.env';
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    if (!empty($key)) {
                        $_ENV[$key] = $value;
                        $_SERVER[$key] = $value;
                        putenv("$key=$value");
                    }
                }
            }
        }
    }
}

// Auto-load on include
EnvConfig::load();
?>