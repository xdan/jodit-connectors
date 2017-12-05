<?php
if (!in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    die('You are not allowed to access this file.');
}

define('JODIT_DEBUG', true);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/tests/TestApplication.php';

$config = require(__DIR__ . "/default.config.php");

if (file_exists(__DIR__ . "/tests/config.php")) {
	$config = array_merge($config, require(__DIR__ . "/tests/config.php"));
}

$fileBrowser = new JoditRestTestApplication($config);

try {
	$fileBrowser->checkPermissions();
	$fileBrowser->execute();
} catch(\ErrorException $e) {
	$fileBrowser->exceptionHandler($e);
}