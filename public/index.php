<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$BANDWIDTH_ACCOUNT_ID = getenv("BANDWIDTH_ACCOUNT_ID");
$BANDWIDTH_API_USER = getenv("BANDWIDTH_API_USER");
$BANDWIDTH_API_PASSWORD = getenv("BANDWIDTH_API_PASSWORD");
$BANDWIDTH_VOICE_APPLICATION_ID = getenv("BANDWIDTH_VOICE_APPLICATION_ID");
$BASE_URL = getenv("BASE_URL");
$PERSONAL_NUMBER = getenv("PERSONAL_NUMBER");

$config = new BandwidthLib\Configuration(
    array(
        "voiceBasicAuthUserName" => $BANDWIDTH_API_USER,
        "voiceBasicAuthPassword" => $BANDWIDTH_API_PASSWORD
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
    global $BANDWIDTH_ACCOUNT_ID, $BANDWIDTH_VOICE_APPLICATION_ID, $BASE_URL, $PERSONAL_NUMBER, $voice_client;
    $data = $request->getParsedBody();

    $body = new BandwidthLib\Voice\Models\ApiCreateCallRequest();
    $body->from = $data['from'];
    $body->to = $PERSONAL_NUMBER;
    $body->answerUrl = $BASE_URL . "/callbacks/outboundCall";;
    $body->applicationId = $BANDWIDTH_VOICE_APPLICATION_ID;
    $body->tag = $data['callId'];

    try {
        $apiResponse = $voice_client->createCall($BANDWIDTH_ACCOUNT_ID, $body);
        # $callId = $apiResponse->getResult()->callId;
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
    global $BANDWIDTH_ACCOUNT_ID, $BANDWIDTH_VOICE_APPLICATION_ID, $BASE_URL, $voice_client;
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
