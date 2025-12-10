<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Force APP_ENV to 'test' before loading env files
$_SERVER['APP_ENV'] = 'test';
$_ENV['APP_ENV'] = 'test';
putenv('APP_ENV=test');

// Load .env.test if it exists, otherwise fallback to .env
$envFile = dirname(__DIR__).'/.env.test';
if (!file_exists($envFile)) {
    $envFile = dirname(__DIR__).'/.env';
}

if (method_exists(Dotenv::class, 'bootEnv')) {
    $dotenv = new Dotenv();
    $dotenv->loadEnv($envFile, 'APP_ENV', 'test');
}

// Ensure APP_ENV remains 'test'
$_SERVER['APP_ENV'] = 'test';
$_ENV['APP_ENV'] = 'test';
putenv('APP_ENV=test');

if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0000);
}
