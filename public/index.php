<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$BW_ACCOUNT_ID = getenv("BW_ACCOUNT_ID");
$BW_USERNAME = getenv("BW_API_USER");
$BW_PASSWORD = getenv("BW_API_PASSWORD");
$BW_VOICE_APPLICATION_ID = getenv("BW_VOICE_APPLICATION_ID");
$BASE_CALLBACK_URL = getenv("BASE_CALLBACK_URL");
$USER_NUMBER = getenv("USER_NUMBER");

$config = new BandwidthLib\Configuration(
    array(
        "voiceBasicAuthUserName" => $BW_USERNAME,
        "voiceBasicAuthPassword" => $BW_PASSWORD
    )
);

// Instantiate Bandwidth Client
$client = new BandwidthLib\BandwidthClient($config);

// Instantiate App
$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

$voice_client = $client->getVoice()->getClient();

$app->post('/callbacks/inboundCall', function (Request $request, Response $response) {
    global $BW_ACCOUNT_ID, $BW_VOICE_APPLICATION_ID, $BASE_CALLBACK_URL, $USER_NUMBER, $voice_client;
    $data = $request->getParsedBody();

    $body = new BandwidthLib\Voice\Models\ApiCreateCallRequest();
    $body->from = $data['from'];
    $body->to = $USER_NUMBER;
    $body->answerUrl = $BASE_CALLBACK_URL . "/callbacks/outboundCall";;
    $body->applicationId = $BW_VOICE_APPLICATION_ID;
    $body->tag = $data['callId'];

    try {
        $apiResponse = $voice_client->createCall($BW_ACCOUNT_ID, $body);
    } catch (BandwidthLib\APIException $e) {
        $response->getBody()->write($e);
        return $response->withStatus(400);
    }

    $bxmlResponse = new BandwidthLib\Voice\Bxml\Response();

    $speakSentence = new BandwidthLib\Voice\Bxml\SpeakSentence("Hold while we connect you");
    $speakSentence->voice("kate");

    $ring = new BandwidthLib\Voice\Bxml\Ring();
    $ring->duration(30);

    $bxmlResponse->addVerb($speakSentence);
    $bxmlResponse->addVerb($ring);

    $response = $response->withStatus(200)->withHeader('Content-Type', 'application/xml');
    $response->getBody()->write($bxmlResponse->toBxml());
    return $response;

});

$app->post('/callbacks/outboundCall', function (Request $request, Response $response) {
    global $BW_ACCOUNT_ID, $BW_VOICE_APPLICATION_ID, $BASE_CALLBACK_URL, $voice_client;
    $data = $request->getParsedBody();

    $bxmlResponse = new BandwidthLib\Voice\Bxml\Response();

    $speakSentence = new BandwidthLib\Voice\Bxml\SpeakSentence("Hold while we connect you. We will begin the bridge now.");
    $speakSentence->voice("kate");

    $bridge = new BandwidthLib\Voice\Bxml\Bridge($data['tag']);

    $bxmlResponse->addVerb($speakSentence);
    $bxmlResponse->addVerb($bridge);

    $response = $response->withStatus(200)->withHeader('Content-Type', 'application/xml');
    $response->getBody()->write($bxmlResponse->toBxml());
    return $response;
});

$app->run();
