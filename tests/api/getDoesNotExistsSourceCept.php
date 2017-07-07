<?php 
$I = new ApiTester($scenario);

$I->wantTo('Get does not exists source');
$I->sendGET('?action=files&source=test2');
$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => false,
    "data" => [
        "code" => 400
    ]
]);
