<?php

// Load Composer autoloader to make Dotenv available
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Load environment variables
if (class_exists('Dotenv\Dotenv') && file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

define('DB_HOST', $_ENV['DB_HOST'] ?? 'database');
define('DB_USER', $_ENV['DB_USER'] ?? 'lamp');
define('DB_PASS', $_ENV['DB_PASS'] ?? 'lamp');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'lamp');
