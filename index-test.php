<?php
if (!in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    die('You are not allowed to access this file.');
}

define('JODIT_DEBUG', true);

require_once 'vendor/autoload.php';
require_once 'tests/TestApplication.php';

$config = include "default.config.php";

$config = array_merge($config, include "./tests/config.php");
$fileBrowser = new JoditRestTestApplication($config);

$fileBrowser->checkPermissions();

$fileBrowser->execute();