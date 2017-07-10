<?php
$I = new ApiTester($scenario);

$I->wantTo('Check moving file to another directory');

$I->sendGET('?action=move&source=test&from=artio.jpg&path=folder1');

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220,
    ]
]);


$I->sendGET('?action=move&source=test&path=&from=folder1/artio.jpg');



