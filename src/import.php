<?php

namespace CloudReader;

require '../vendor/autoload.php';

use Elasticsearch\Client;

use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Elasticsearch\ClientBuilder;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;

// Set root dir
chdir(dirname(__DIR__));

define('DEBUG', false);

// Define some important values
define('LOG_DIR', './logs/');
define('APP_LOG_FILE', 'application.log');
define('ELASTIC_LOG_FILE', 'elastic.log');

define('LOG_GROUP_NAME', 'VAMS-BI-PROD');
define('INDEX', 'vams-events');
define('TYPE', 'content-view');

// Create and application logger
$logLevel = (DEBUG) ? Logger::DEBUG : Logger::WARNING;

$logger = new Logger('app');
$logger->pushHandler(new StreamHandler(LOG_DIR . APP_LOG_FILE, $logLevel));

// Create CloudwatchLogs Client
$cloudwatch = new CloudWatchLogsClient([
    'region' => 'us-east-1',
    'version' => '2014-03-28',
]);

// Create Elastic Client
$loggerElastic = new Logger('elastic');
$loggerElastic->pushHandler(new StreamHandler(LOG_DIR . ELASTIC_LOG_FILE, Logger::NOTICE));
$elastic = ClientBuilder::create()
    ->setLogger($loggerElastic)
    ->setHosts(['docker.dev:9200'])
    ->build();

// Delete indexes if exists
try {
    if (false) {
        $response = $elastic->indices()->delete([
            'index' => 'app-logs',
        ]);
        $response = $elastic->indices()->delete([
            'index' => 'access-logs',
        ]);
        $response = $elastic->indices()->delete([
            'index' => 'vams-events',
        ]);
    }
} catch (Missing404Exception $e) {
    $logger->debug($e->getMessage());
}

// Create all indexes
try {
    $params = [];
    $params []= include('./mapping/mapping_access-logs.php');
    $params []= include('./mapping/mapping_app-logs.php');
    $params []= include('./mapping/mapping_vams-events.php');

    foreach ($params as $p) {
        $response = $elastic->indices()->create($p);
    }
} catch (BadRequest400Exception $e) {
    $logger->debug($e->getMessage());
}


$elasticIndexer = new ElasticIndexer($logger, $elastic);
$elasticIndexer->setIndex(INDEX);
$elasticIndexer->setType(TYPE);

$vamsParser = new VamsAccessLogParser($logger, $elasticIndexer);
$vamsEventParser = new VamsEventLogParser();

// Create CloudWatch Logs Reader
$reader = new CloudwatchLogsReader($logger, $cloudwatch, $vamsEventParser, [
    //'limit' => 5,
    'logGroupName' => LOG_GROUP_NAME,
    'logStreamName' => 'vamsd-events-content_view',
]);

while (true) {

    $logger->critical('Running again');

    try {
        foreach ($reader->generate($elasticIndexer) as $message) {
            $id = uniqid('', true);
            // @TODO batch
            $elasticIndexer->indexOne($id, $message);
        }
    } catch (\OutOfBoundsException $e) {
        $logger->warning('Fetch from cloud returned no messages', ['error' => $e->getMessage() ]);
        sleep(3);
    } catch (BadRequest400Exception $e) {
        $logger->warning('Bad Request sent to elastic', ['error' => $e->getMessage() ]);
        exit;
    }
};
