<?php
$autoloader = require __DIR__ . '/vendor/autoload.php';
$config = require  __DIR__ . '/config.php';
$config['autoloader'] = $autoloader;

return $config;