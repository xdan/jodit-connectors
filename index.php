<?php
define('JODIT_DEBUG', false);

require_once 'vendor/autoload.php';
require_once 'core/JoditApplication.php';
require_once 'Application.php';

$config = include "default.config.php";
$config = array_merge($config, include "config.php");


$fileBrowser = new \JoditRestApplication($config);

$fileBrowser->checkPermissions();

$fileBrowser->execute();