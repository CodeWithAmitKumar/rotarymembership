<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__);
}

if (!defined('APP_BASE_URL')) {
    $document_root = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $app_root = realpath(APP_ROOT);
    $base_url = '/';

    if ($document_root && $app_root) {
        $document_root = str_replace('\\', '/', rtrim($document_root, '\\/'));
        $app_root = str_replace('\\', '/', rtrim($app_root, '\\/'));

        if (strpos($app_root, $document_root) === 0) {
            $relative_path = trim(substr($app_root, strlen($document_root)), '/');
            $base_url = '/' . ($relative_path === '' ? '' : $relative_path . '/');
        }
    }

    define('APP_BASE_URL', $base_url);
}

if (!function_exists('app_path')) {
    function app_path($path = '') {
        $path = (string) $path;

        if ($path === '') {
            return APP_ROOT;
        }

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) || strpos($path, DIRECTORY_SEPARATOR) === 0 || strpos($path, '/') === 0) {
            return $path;
        }

        $path = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        return $path === '' ? APP_ROOT : APP_ROOT . DIRECTORY_SEPARATOR . $path;
    }
}

if (!function_exists('app_url')) {
    function app_url($path = '') {
        $path = (string) $path;

        if ($path === '') {
            return APP_BASE_URL;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');
        return $path === '' ? APP_BASE_URL : APP_BASE_URL . $path;
    }
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'rotarymember');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
