<?php 
$I = new ApiTester($scenario);

$I->wantTo('Get all folders from Test source');
$I->sendGET('?action=folders&source=test&path=folder1');
$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220
    ]
]);

$I->seeResponseJsonMatchesXpath('//data/sources/test/folders');
$I->dontSeeResponseJsonMatchesXpath('//data/sources/folder1/folders');
