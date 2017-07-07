<?php 
$I = new ApiTester($scenario);

$I->wantTo('Get not exists folder from source');
$I->sendGET('?action=files&source=test&path=qwerty/qwerty');
$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => false,
    "data" => [
        "code" => 404
    ]
]);
