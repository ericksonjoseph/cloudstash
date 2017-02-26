<?php

require 'vendor/autoload.php';

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Elasticsearch\ClientBuilder;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

define('MAX_CYCLES', 100);

$app = new App();

for ($i=0; $i<MAX_CYCLES; $i++) {
    $app->work();
}

class App {

    const DEBUG = true;
    const LOG_DIR = 'logs/';
    const LOG_FILE = 'application.log';
    const ELASTIC_LOG_FILE = 'elastic.log';

    const ELASTIC_SOCKET = 'docker.dev:9200';

    private $elastic;

    private $nextToken;

    private $logParams = [
        //'limit' => 2,
        'logGroupName' => 'VAMS-BI-PROD',
        'logStreamName' => 'vamsd-events-content_view',
    ];

    public function __construct() {

        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler(self::LOG_DIR . self::ELASTIC_LOG_FILE, Logger::DEBUG));

        $this->elastic = ClientBuilder::create()
            ->setLogger($logger)
            ->setHosts([self::ELASTIC_SOCKET])
            ->build();
    }

    public function work() {

        $this->log('', ['action'=> 'work']);

        $events = $this->getEvents();

        foreach ($events as $evt) {

            $message = json_decode($evt['message']);

            $this->indexDoc($message->data->id, $message->data);
        }

    }

    private function indexDoc(string $id, stdClass $body) {

        $params = [
            'index' => 'vams',
            'type' => 'content-view',
            'id' => $id,
            'body' => $body
        ];

        $response = $this->elastic->index($params);

        $this->log('', $response);
    }

    private function log(string $msg, array $data = []) {

        if (!self::DEBUG) return false;

        if ($data) {
            $msg .= json_encode($data);
        }

        file_put_contents(self::LOG_DIR . self::LOG_FILE, $msg . PHP_EOL, FILE_APPEND);
    }

    private function getEvents() {

        //return unserialize('a:5:{i:0;a:3:{s:9:"timestamp";i:1488050403781;s:7:"message";s:543:"{"data":{"id":"928727-cf61016b-d92c-4b13-9ac2-c1d82e217c3a","creationDate":"2017-02-25 19:20:03.3439","userId":"928727","assetId":"30343","channelId":null,"listingId":null,"programType":null,"viewStarted":null,"viewEnded":null,"viewDuration":5340,"timelinePosition":1290,"deviceInfo":"type/TV make/apple model/Apple TV uid/961C0A74-E4BD-458A-B85E-E975DC3B5E41 os/tvOS osVersion/10.1.1 blimVersion/1.4.2-44","state":"RETAINED"},"destination":"content_view","event":"createdContentView","level":"info","msg":"vams","time":"2017-02-25T19:20:03Z"}";s:13:"ingestionTime";i:1488050404852;}i:1;a:3:{s:9:"timestamp";i:1488050403781;s:7:"message";s:547:"{"data":{"id":"2080753-71b98941-ed40-40f6-97dd-f30ef739d793","creationDate":"2017-02-25 19:20:03.3490","userId":"2080753","assetId":"19757","channelId":null,"listingId":null,"programType":null,"viewStarted":null,"viewEnded":null,"viewDuration":2460,"timelinePosition":600,"deviceInfo":"type/Phone make/apple model/iPhone 5s uid/DED54C2E-99FE-4F2B-A7BA-D5B87E7D9C6D os/iOS osVersion/10.2.1 blimVersion/1.4.2-94","state":"RETAINED"},"destination":"content_view","event":"createdContentView","level":"info","msg":"vams","time":"2017-02-25T19:20:03Z"}";s:13:"ingestionTime";i:1488050404852;}i:2;a:3:{s:9:"timestamp";i:1488050403781;s:7:"message";s:511:"{"data":{"id":"1902661-5a339da3-44d6-4084-b19a-39a18862ae52","creationDate":"2017-02-25 19:20:03.3564","userId":"1902661","assetId":"7606","channelId":null,"listingId":null,"programType":null,"viewStarted":null,"viewEnded":null,"viewDuration":5229,"timelinePosition":4937,"deviceInfo":"type/TABLET make/amlogic model/M9S uid/LMY47V os/Android osVersion/22 blimVersion/2.1.0","state":"RETAINED"},"destination":"content_view","event":"createdContentView","level":"info","msg":"vams","time":"2017-02-25T19:20:03Z"}";s:13:"ingestionTime";i:1488050404852;}i:3;a:3:{s:9:"timestamp";i:1488050403781;s:7:"message";s:547:"{"data":{"id":"2127431-5852f904-e2ee-46de-9a49-a4153a597156","creationDate":"2017-02-25 19:20:03.3588","userId":"2127431","assetId":"8369","channelId":null,"listingId":null,"programType":null,"viewStarted":null,"viewEnded":null,"viewDuration":1320,"timelinePosition":1050,"deviceInfo":"type/Phone make/apple model/iPhone 5s uid/98015028-D4DA-4C7E-8DC3-B38DF8586796 os/iOS osVersion/10.2.1 blimVersion/1.4.2-94","state":"RETAINED"},"destination":"content_view","event":"createdContentView","level":"info","msg":"vams","time":"2017-02-25T19:20:03Z"}";s:13:"ingestionTime";i:1488050404852;}i:4;a:3:{s:9:"timestamp";i:1488050403782;s:7:"message";s:547:"{"data":{"id":"1292575-dd88a0ae-cb6b-414c-9674-05ed965f8dfc","creationDate":"2017-02-25 19:20:03.3603","userId":"1292575","assetId":"21362","channelId":null,"listingId":null,"programType":null,"viewStarted":null,"viewEnded":null,"viewDuration":2580,"timelinePosition":1439,"deviceInfo":"type/Phone make/apple model/iPhone 7 uid/23082904-39DE-404C-A6B2-420DE627F89A os/iOS osVersion/10.1.1 blimVersion/1.4.2-94","state":"RETAINED"},"destination":"content_view","event":"createdContentView","level":"info","msg":"vams","time":"2017-02-25T19:20:03Z"}";s:13:"ingestionTime";i:1488050404852;}}');

        $client = new CloudWatchLogsClient([
            'region' => 'us-east-1',
            'version' => '2014-03-28',
        ]);

        $result = $client->getLogEvents($this->logParams);

        $this->logParams['nextToken'] = $result->get('nextForwardToken');

        return $result->get('events');
    }

    private function getLogParams() {
        return $this->logParams;
    }
}
