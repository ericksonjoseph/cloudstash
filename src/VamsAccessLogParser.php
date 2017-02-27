<?php

namespace CloudReader;

use Psr\Log\LoggerInterface;

class VamsAccessLogParser implements LogParserInterface {

    private $appLogKeys = [
        'time',
        'level',
        'msg',
        'data-type',
        'stream-type',
        'stream-id',
        'created',
        'updated',
        'user',
        'asset',
        'video',
        'state'
    ];

    private $appLogKeysF2 = [
        'time',
        'level',
        'msg',
        'asset',
        'stream-id',
        'data-type',
        'user',
        'video',
    ];

    private $appLogKeysF3 = [
        'time',
        'level',
        'msg',
        'asset',
        'error',
        'user',
    ];

    private $appLogKeysF4 = [
        'time',
        'level',
        'msg',
    ];

    private $accessLogKeys = [
        'text',
        'ip',
        'date',
        'method',
        'url',
        'protocol',
        'statusCode',
        'user-agent'
    ];

    /**
     * Since vams access and app logs are tangled together we pass
     * the indexer to allow the parser to switch indexes
     *
     * @param ElasticIndexer $indexer
     * @access public
     * @return void
     */
    public function __construct(LoggerInterface $logger, ElasticIndexer $indexer)
    {
        $this->logger = $logger;
        $this->indexer = $indexer;
    }

    /**
     * Ugly function to parse ugly logs
     *
     * @param string $message
     * @return \stdObject
     */
    public function parse(string $message) {

        $this->indexer->setIndex('app-logs');

        $output_array = [];
        $input_line = $message;

        preg_match("/\w+=\"([^\"]+)\" \w+=([\d-:\w\"]+) \w+=\"([^\"]+)\" \w+=(AssetUserStream\([^)]+\)) \w+=([^ ]+)/", $input_line, $output_array);

        //echo "first check count = " . count($output_array) . PHP_EOL;

        // We may have to do some other stuff
        if (count($output_array) !== 6) {
            preg_match("/\w+=\"([^\"]+)\" \w+=([^ ]+) \w+=\"([^\"]+)\" \w+=([^ ]+) \w+=([^ ]+) \w+=([^ ]+) \w+=([^ ]+) \w+=([^ ]+)/", $input_line, $output_array);

            //print_r($output_array);

            if (count($output_array) === 9) {
                return (object) $this->parseAppLogF2($output_array);
            }

            return (object) $this->parseAccessLog($message);
        }

        array_shift($output_array);

        if (!$additions = $this->parseAssetUserStream($output_array[3])) {
            $this->logger->notice('Failed to parse the asset user stream', ['string' => $output_array[3] ]);
            return false;
        }
        unset($output_array[3]);

        $merged_array = array_merge($output_array, $additions);

        $combined_array = array_combine($this->appLogKeys, $merged_array);

        if (empty($combined_array)){
            $this->logger->notice('Combination Failed', [
                'app-log-keys' => $this->appLogKeys,
                'array-of-values' => $merged_array,
            ]);
            return false;
        }

        return (object) $combined_array;
    }

    private function parseAppLogF2(array $array): array
    {
        array_shift($array);

        $combined_array = array_combine($this->appLogKeysF2, $array);

        return $combined_array;
    }

    private function parseAppLogF3(array $array): array
    {
        array_shift($array);

        $combined_array = array_combine($this->appLogKeysF3, $array);

        return $combined_array;
    }

    private function parseAppLogF4(array $array): array
    {
        array_shift($array);

        $combined_array = array_combine($this->appLogKeysF4, $array);

        return $combined_array;
    }

    private function parseAssetUserStream(string $string): array
    {
        $input_line = $string;
        $output_array = [];

        preg_match("/(\w+)\(id=([^,]+), created=([^,]+), updated=([^,]+), user=([^,]+), asset=([^,]+), video=([^,]+), state=([^)]+)/", $input_line, $output_array);
        if (count($output_array) !== 9) {
            $this->logger->notice('Failed to match asset user stream string', [
                'string' => $string,
            ]);
            return [];
        }

        array_shift($output_array);

        return $output_array;
    }

    public function parseAccessLog(string $message)
    {
        $output_array = [];
        $input_line = $message;
        $keys = $this->accessLogKeys;

        preg_match("/(\d+.\d+.\d+.\d+ )- - \[(.*)\] \"([^ ]+) ([^ ]+) ([^ ]+)\" (\d+) \d+ \".*\" \"(.*)\"/", $input_line, $output_array);

        if (empty($output_array)) {

            preg_match('/\w+=\\"([^\\"]+)\\" \w+=([^ ]+) \w+=\\"([^\\"]+)\\" \w+=(\d+) \w+=\\"([^\\"]+)\\" \w+=(.+)/', $input_line, $output_array);

            if (count($output_array) === 7) {
            $this->logger->notice('Matching F3');
                return $this->parseAppLogF3($output_array);
            }

            preg_match('/\w+=\\"([^\\"]+)\\" \w+=([^ ]+) \w+=\\"([^\\"]+)\\"/', $input_line, $output_array);
            if (count($output_array) === 4) {
            $this->logger->notice('Matching F4');
                return $this->parseAppLogF4($output_array);
            }

            $this->logger->notice('Failed to match access log', [
                'string' => $message,
            ]);
            return false;
        }

        $this->indexer->setIndex('access-logs');

        return array_combine($keys, $output_array);
    }
}
