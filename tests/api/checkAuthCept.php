<?php 
$I = new ApiTester($scenario);

$I->wantTo('Check checkPermissions method');
$I->sendGET('?auth=1'); // see file TestApplication.php
$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => false,
    "data" => [
        "code" => 403
    ]
]);
