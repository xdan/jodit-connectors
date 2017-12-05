<?php
$I = new ApiTester($scenario);

$I->wantTo('Remove image from server');

$I->sendGET('?action=uploadremote&source=test&url=' . urlencode('https://xdsoft.net/jodit/stuf/icon-joomla.png'));

$I->sendGET('?action=remove&source=test&name=icon-joomla.png');

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220,
    ]
]);


$I->sendGET('?action=remove&source=test&name=icon-joomla.png'); // try remove again

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => false,
    "data" => [
        "code" => 400,
    ]
]);



