<?php
$I = new ApiTester($scenario);

$I->wantTo('Try create folder');

$name = 'test' . rand(10000, 100000);

$I->sendGET('?action=create&source=test&name=' . $name);

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220,
    ]
]);


$I->sendGET('?action=remove&source=test&name=' . $name); // remove new folder

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220,
    ]
]);




