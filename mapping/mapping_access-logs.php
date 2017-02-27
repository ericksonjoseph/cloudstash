<?php
return [
    'index' => 'access-logs',
    'body' => [
        'mappings' => [
            'vams' => [
                'properties' => [
                    'date' => [
                        'type' => 'date',
                        'format' => "dd/MMM/YYYY:kk:mm:ss Z"
                    ],
                    'statusCode' => [
                        'type' => 'integer',
                    ]
                ]
            ]
        ]
    ]
];
