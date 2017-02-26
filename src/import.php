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

// Set root dir
chdir(dirname(__DIR__));

// Define some important values
define('APP_LOG_FILE', 'application.log');
define('ELASTIC_LOG_FILE', 'elastic.log');
define('LOG_DIR', './logs/');

$logger = new Logger('app');
$logger->pushHandler(new StreamHandler(LOG_DIR . APP_LOG_FILE, Logger::DEBUG));

$loggerElastic = new Logger('elastic');
$loggerElastic->pushHandler(new StreamHandler(LOG_DIR . ELASTIC_LOG_FILE, Logger::NOTICE));

$cloudwatch = new CloudWatchLogsClient([
    'region' => 'us-east-1',
    'version' => '2014-03-28',
]);

$elastic = ClientBuilder::create()
    ->setLogger($loggerElastic)
    ->setHosts(['docker.dev:9200'])
    ->build();

$params = include('./vams-events_mapping.php');

// Delete index
try {
    $response = $elastic->indices()->delete([
        'index' => 'vams-events',
    ]);
} catch (Missing404Exception $e) {
    $logger->debug($e->getMessage());
}

// Create index with correct mappings
try {
    $response = $elastic->indices()->create($params);
} catch (BadRequest400Exception $e) {
    $logger->debug($e->getMessage());
}

$reader = new CloudwatchLogsReader($logger, $cloudwatch);
$elasticIndexer = new ElasticIndexer($logger, $elastic);

foreach ($reader->generate() as $message) {
    $id = uniqid('', true);
    $elasticIndexer->indexOne($id, $message);
}
