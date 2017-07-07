<?php 
$I = new ApiTester($scenario);

$I->wantTo('Get all items from all sources');
$I->sendGET('?action=files');
$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220
    ]
]);

$I->seeResponseJsonMatchesXpath('//data/sources/test/files/file');
$I->seeResponseJsonMatchesXpath('//data/sources/folder1/files/file');
