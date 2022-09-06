<?php
define('JODIT_DEBUG', false);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Application.php';

$config = require(__DIR__ . "/default.config.php");

if (file_exists(__DIR__ . "/config.php")) {
	$config = array_merge($config, require(__DIR__ . "/config.php"));
}

$fileBrowser = new \JoditRestApplication($config);

try {
    set_time_limit($fileBrowser->config->timeoutLimit);
    ini_set('upload_max_filesize', $fileBrowser->config->maxUploadFileSize ?: '8M');
    ini_set('memory_limit', $fileBrowser->config->memoryLimit ?: '128M');
    ini_set('expose_php', 'off');

    $fileBrowser->checkAuthentication();
	$fileBrowser->execute();
} catch(\ErrorException $e) {
	$fileBrowser->exceptionHandler($e);
}

