<?php
$I = new ApiTester($scenario);

$I->wantTo('Crop image');

$name = 'test' . rand(10000, 20000);

$I->sendPOST('',  [
    'action' => 'crop',
    'source' => 'test',
    'box' => [
        'w' => 30,
        'h' => 30,
        'x' => 5,
        'y' => 5,
    ],
    'name' => 'artio.jpg',
    'newname' => $name
]);

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220,
    ]
]);


// remove new file
$I->sendGET('?action=remove&source=test&name=' . $name . '.jpg');
$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220,
    ]
]);