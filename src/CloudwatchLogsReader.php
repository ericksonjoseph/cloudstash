<?php

namespace CloudReader;

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Psr\Log\LoggerInterface;

class CloudwatchLogsReader {

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CloudWatchLogsClient
     * @var CloudWatchLogsClient
     */
    private $client;

    /**
     * Class used to parse the logs after the client fetches them
     * @var Parser
     */
    private $parser;

    /**
     * Holds the parameters to be used in the get-log-events call
     * @var array
     */
    private $clientParams;

    private $describeLogStreamsParams = array(
        //'logGroupName' => $config['logGroupName'],
        'orderBy' => 'LastEventTime',
        'descending' => true,
        'limit' => 2,
    );

    /**
     * Holds information about the client params i.e required
     * @var array
     */
    private $cloudParamsSettings = [
        'logGroupName' => true,
        //'logStreamName' => true
    ];

    public function __construct(LoggerInterface $logger, CloudWatchLogsClient $client, LogParserInterface $parser, array $config)
    {
        if (!empty(array_diff_key($this->cloudParamsSettings, $config))) {
            throw new \InvalidArgumentException('Could not build a Reader with the given config');
        }

        $this->clientParams = $config;
        $this->logger = $logger;
        $this->client = $client;
        $this->parser = $parser;

        if (!isset($config['logStreamName'])) {

            $this->describeLogStreamsParams['logGroupName'] = $config['logGroupName'];

            if (DEBUG) {
                $result = unserialize(file_get_contents('./example-describeLogStreams-response.txt'));
            } else {
                $result = $this->client->describeLogStreams($this->describeLogStreamsParams);
            }

            // @TODO decide if we will page here
            //$this->clientParams['describeLogStreamsNextToken'] = $result->get('nextToken');
            $this->clientParams['logStreamName'] = $result->get('logStreams')[0]['logStreamName'];
        }
    }

    public function generate()
    {
        while (true) {
            foreach ($this->work() as $i => $message) {
                yield $message;
            }
        }
    }

    public function work()
    {

        $this->logger->warning('Event', ['action'=> 'work']);

        $events = $this->getEvents();

        if (empty($events)) {
            throw new \OutOfBoundsException('Fetch returned no events');
        }

        foreach ($events as $evt) {
            if (!$message = $this->parser->parse($evt['message'])) {
                $this->logger->notice('Failed to parse the incoming message', ['incoming-message' => $evt['message'] ]);
                continue;
            }

            yield $message;
        }
        if (DEBUG) {
            sleep(2);
        }
    }

    private function getEvents()
    {
        if (DEBUG) {
            //return unserialize('a:5:{i:0;a:3:{s:9:"timestamp";i:1488050403781;s:7:"message";s:543:"{"data":{"id":"928727-cf61016b-d92c-4b13-9ac2-c1d82e217c3a","creationDate":"2017-02-25 19:20:03.3439","userId":"928727","assetId":"30343","channelId":null,"listingId":null,"programType":null,"viewStarted":null,"viewEnded":null,"viewDuration":5340,"timelinePosition":1290,"deviceInfo":"type/TV make/apple model/Apple TV uid/961C0A74-E4BD-458A-B85E-E975DC3B5E41 os/tvOS osVersion/10.1.1 blimVersion/1.4.2-44","state":"RETAINED"},"destination":"content_view","event":"createdContentView","level":"info","msg":"vams","time":"2017-02-25T19:20:03Z"}";s:13:"ingestionTime";i:1488050404852;}i:1;a:3:{s:9:"timestamp";i:1488050403781;s:7:"message";s:547:"{"data":{"id":"2080753-71b98941-ed40-40f6-97dd-f30ef739d793","creationDate":"2017-02-25 19:20:03.3490","userId":"2080753","assetId":"19757","channelId":null,"listingId":null,"programType":null,"viewStarted":null,"viewEnded":null,"viewDuration":2460,"timelinePosition":600,"deviceInfo":"type/Phone make/apple model/iPhone 5s uid/DED54C2E-99FE-4F2B-A7BA-D5B87E7D9C6D os/iOS osVersion/10.2.1 blimVersion/1.4.2-94","state":"RETAINED"},"destination":"content_view","event":"createdContentView","level":"info","msg":"vams","time":"2017-02-25T19:20:03Z"}";s:13:"ingestionTime";i:1488050404852;}i:2;a:3:{s:9:"timestamp";i:1488050403781;s:7:"message";s:511:"{"data":{"id":"1902661-5a339da3-44d6-4084-b19a-39a18862ae52","creationDate":"2017-02-25 19:20:03.3564","userId":"1902661","assetId":"7606","channelId":null,"listingId":null,"programType":null,"viewStarted":null,"viewEnded":null,"viewDuration":5229,"timelinePosition":4937,"deviceInfo":"type/TABLET make/amlogic model/M9S uid/LMY47V os/Android osVersion/22 blimVersion/2.1.0","state":"RETAINED"},"destination":"content_view","event":"createdContentView","level":"info","msg":"vams","time":"2017-02-25T19:20:03Z"}";s:13:"ingestionTime";i:1488050404852;}i:3;a:3:{s:9:"timestamp";i:1488050403781;s:7:"message";s:547:"{"data":{"id":"2127431-5852f904-e2ee-46de-9a49-a4153a597156","creationDate":"2017-02-25 19:20:03.3588","userId":"2127431","assetId":"8369","channelId":null,"listingId":null,"programType":null,"viewStarted":null,"viewEnded":null,"viewDuration":1320,"timelinePosition":1050,"deviceInfo":"type/Phone make/apple model/iPhone 5s uid/98015028-D4DA-4C7E-8DC3-B38DF8586796 os/iOS osVersion/10.2.1 blimVersion/1.4.2-94","state":"RETAINED"},"destination":"content_view","event":"createdContentView","level":"info","msg":"vams","time":"2017-02-25T19:20:03Z"}";s:13:"ingestionTime";i:1488050404852;}i:4;a:3:{s:9:"timestamp";i:1488050403782;s:7:"message";s:547:"{"data":{"id":"1292575-dd88a0ae-cb6b-414c-9674-05ed965f8dfc","creationDate":"2017-02-25 19:20:03.3603","userId":"1292575","assetId":"21362","channelId":null,"listingId":null,"programType":null,"viewStarted":null,"viewEnded":null,"viewDuration":2580,"timelinePosition":1439,"deviceInfo":"type/Phone make/apple model/iPhone 7 uid/23082904-39DE-404C-A6B2-420DE627F89A os/iOS osVersion/10.1.1 blimVersion/1.4.2-94","state":"RETAINED"},"destination":"content_view","event":"createdContentView","level":"info","msg":"vams","time":"2017-02-25T19:20:03Z"}";s:13:"ingestionTime";i:1488050404852;}}');
            return include('./example-getEvents-response.vams-prod.php');
        }

        $t = $this->clientParams['nextToken'] ?? '';
        $this->logger->warning('Fetching log events with token: ' . $t);

        $result = $this->client->getLogEvents($this->clientParams);

        $this->clientParams['nextToken'] = $result->get('nextForwardToken');

        /*
        $x = $result->get('events');
        file_put_contents('./example-getEvents-response.vams-prod.php', var_export($x, true));
        exit;
         */
        return $result->get('events');
    }
}
