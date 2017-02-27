<?php
return [
    'index' => 'app-logs',
    'body' => [
        'mappings' => [
            'vams' => [
                'properties' => [
                    'time' => [
                        'type' => 'date',
                        'format' => "yyyy-MM-dd'T'HH:mm:ssZ"
                    ],
                    'created' => [
                        'type' => 'date',
                        'format' => 'epoch_millis'
                    ],
                    'updated' => [
                        'type' => 'date',
                        'format' => 'epoch_millis'
                    ],
                    'asset' => [
                        'type' => 'integer'
                    ],
                    'video' => [
                        'type' => 'float'
                    ]
                ]
            ]
        ]
    ]
];
