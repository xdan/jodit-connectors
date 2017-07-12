<?php
$I = new ApiTester($scenario);

$I->wantTo('Check cross origin request method');
$I->haveHttpHeader('Access-Control-Request-Method', 'POST');
$I->sendOPTIONS('?action=files'); // see file TestApplication.php
$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeHttpHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
$I->seeHttpHeader('Access-Control-Allow-Origin', '*');
$I->seeHttpHeader('Access-Control-Allow-Credentials', 'true');
$I->seeHttpHeader('Access-Control-Allow-Headers', 'Origin,X-Requested-With,Content-Type,Accept');
$I->seeHttpHeader('Access-Control-Max-Age', '86400');
$I->seeResponseEquals('');

$I->sendGET('?action=files'); // see file TestApplication.php
$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();
$I->dontSeeHttpHeader('Access-Control-Allow-Methods');
$I->seeHttpHeader('Access-Control-Allow-Credentials', 'true');
$I->seeHttpHeader('Access-Control-Allow-Headers', 'Origin,X-Requested-With,Content-Type,Accept');
$I->seeHttpHeader('Access-Control-Max-Age', '86400');
