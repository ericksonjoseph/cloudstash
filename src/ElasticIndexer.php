<?php

namespace CloudReader;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

class ElasticIndexer {

    private $logger;

    private $elastic;

    private $elasticParams = [
        'index' => 'vams-events',
        'type' => 'content-view',
    ];

    public function __construct(LoggerInterface $logger, Client $elastic) {
        $this->logger = $logger;
        $this->elastic = $elastic;
    }

    public function indexOne(string $id, \stdClass $body)
    {
        $this->elasticParams['id'] = $id;
        $this->elasticParams['body'] = $body;

        $response = $this->elastic->index($this->elasticParams);

        $this->logger->debug('', $response);
    }
}
