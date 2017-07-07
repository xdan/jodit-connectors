<?php 
$I = new ApiTester($scenario);

$I->wantTo('Get all folders from all sources');
$I->sendGET('?action=folders');
$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220
    ]
]);

$I->seeResponseJsonMatchesXpath('//data/sources/test/folders');
$I->seeResponseJsonMatchesXpath('//data/sources/folder1/folders');
