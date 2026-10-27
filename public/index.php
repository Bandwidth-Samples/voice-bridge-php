<?php

use Bandwidth\Api\CallsApi;
use Bandwidth\Configuration;
use Bandwidth\Model\Bxml\Bridge;
use Bandwidth\Model\Bxml\Hangup;
use Bandwidth\Model\Bxml\Response as BxmlResponse;
use Bandwidth\Model\Bxml\Ring;
use Bandwidth\Model\Bxml\SpeakSentence;
use Bandwidth\Model\CreateCall;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$BW_ACCOUNT_ID = getenv('BW_ACCOUNT_ID');
$BW_CLIENT_ID = getenv('BW_CLIENT_ID');
$BW_CLIENT_SECRET = getenv('BW_CLIENT_SECRET');
$BW_VOICE_APPLICATION_ID = getenv('BW_VOICE_APPLICATION_ID');
$BW_NUMBER = getenv('BW_NUMBER');
$USER_NUMBER = getenv('USER_NUMBER');
$BASE_CALLBACK_URL = getenv('BASE_CALLBACK_URL');

$config = Configuration::getDefaultConfiguration()
    ->setClientId($BW_CLIENT_ID)
    ->setClientSecret($BW_CLIENT_SECRET);
$callsApi = new CallsApi(new Client(), $config);

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

function parseBody(Request $request): ?array
{
    $data = json_decode($request->getBody()->getContents(), true);
    return is_array($data) ? $data : null;
}

function xmlResponse(Response $response, BxmlResponse $bxml): Response
{
    $response->getBody()->write($bxml->toBxml());
    return $response->withHeader('Content-Type', 'application/xml');
}

$app->post('/callbacks/inbound/voice', function (Request $request, Response $response) use ($callsApi, $BW_ACCOUNT_ID, $BW_VOICE_APPLICATION_ID, $BW_NUMBER, $USER_NUMBER, $BASE_CALLBACK_URL) {
    $data = parseBody($request);
    if (($data['eventType'] ?? null) !== 'initiate') {
        return $response->withStatus(400);
    }

    $createCall = new CreateCall([
        'application_id' => $BW_VOICE_APPLICATION_ID,
        'to' => $USER_NUMBER,
        'from' => $BW_NUMBER,
        'answer_url' => "{$BASE_CALLBACK_URL}/callbacks/outbound/voice",
        'tag' => $data['callId'] ?? null,
    ]);

    try {
        $callsApi->createCall($BW_ACCOUNT_ID, $createCall);
        $bxml = new BxmlResponse([
            new SpeakSentence('Hold while we connect you.'),
            new Ring(duration: 30),
        ]);
    } catch (Throwable $e) {
        error_log('Failed to create call: ' . $e->getMessage());
        $bxml = new BxmlResponse([
            new SpeakSentence('An error occurred. Ending call.'),
            new Hangup(),
        ]);
    }

    return xmlResponse($response, $bxml);
});

$app->post('/callbacks/outbound/voice', function (Request $request, Response $response) {
    $data = parseBody($request);
    if ($data === null) {
        return $response->withStatus(400);
    }

    $bxml = new BxmlResponse();

    switch ($data['eventType'] ?? null) {
        case 'answer':
            $bxml = new BxmlResponse([
                new SpeakSentence('Hold while we connect you. We will begin to bridge you now.'),
                new Bridge($data['tag'] ?? ''),
            ]);
            break;
        case 'disconnect':
            error_log("Call {$data['callId']} has disconnected");
            break;
        default:
            error_log('Unexpected event type ' . ($data['eventType'] ?? 'null') . ' received');
            break;
    }

    return xmlResponse($response, $bxml);
});

$app->run();
