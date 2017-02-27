<?php

namespace CloudReader;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

class ElasticIndexer {

    private $logger;

    /**
     * Client
     * @var mixed
     */
    private $client;

    /**
     * Hods the vars that will be passed to the index call
     * @var mixed
     */
    private $clientParams = [];

    public function __construct(LoggerInterface $logger, Client $client) {
        $this->logger = $logger;
        $this->client = $client;
    }

    public function setIndex(string $index)
    {
        $this->clientParams['index'] = $index;
    }

    public function setType(string $type)
    {
        $this->clientParams['type'] = $type;
    }

    public function indexOne(string $id, \stdClass $body)
    {
        $this->clientParams['id'] = $id;
        $this->clientParams['body'] = $body;

        $response = $this->client->index($this->clientParams);

        $this->logger->debug('', $response);
    }
}
