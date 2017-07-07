<?php
$I = new ApiTester($scenario);

$I->wantTo('Check uploding remote image from another site');

$I->sendGET('?action=uploadremote&source=test&url=' . urlencode('http://xdsoft.net/jodit/stuf/icon-joomla.png1'));
$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => false,
    "data" => [
        "code" => 406
    ]
]);

$I->sendGET('?action=uploadremote&source=test&url=' . urlencode('icon-joomla.png'));
$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => false,
    "data" => [
        "code" => 400
    ]
]);


$I->sendGET('?action=uploadremote&source=test&url=' . urlencode('http://xdsoft.net/jodit/stuf/icon-joomla.png'));
$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220
    ]
]);

$I->seeResponseJsonMatchesXpath('//data/sources/test/newfilename');






