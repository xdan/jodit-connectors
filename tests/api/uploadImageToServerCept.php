<?php
$I = new ApiTester($scenario);

$I->wantTo('Check uploading image from another site');

$I->sendPOST('',  [
    'action' => 'upload',
    'source' => 'test'
], ['files' => [
    realpath(__DIR__ . '/../test.png')
]]);

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();
//\Codeception\Util\Debug::debug($I->em);die();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220,
        "files" => [
            "test.png"
        ]
    ]
]);


$I->sendPOST('',  [
    'action' => 'upload',
    'source' => 'test'
], ['files' => [
    realpath(__DIR__ . '/../config.php')
]]);

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => false,
    "data" => [
        "code" => 403,
    ]
]);



$I->sendPOST('',  [
    'action' => 'upload',
    'source' => 'folder1' // see config.php and maxfilesize option
], ['files' => [
    realpath(__DIR__ . '/../test.png')
]]);

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => false,
    "data" => [
        "code" => 403,
    ]
]);


$I->sendGET('?action=remove&source=test&name=test.png');
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220
    ]
]);



