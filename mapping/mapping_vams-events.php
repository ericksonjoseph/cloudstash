<?php
return [
    'index' => 'vams-events',
    'body' => [
        'mappings' => [
            'content-view' => [
                'properties' => [
                    'creationDate' => [
                        'type' => 'date',
                        'format' => 'yyyy-MM-dd HH:mm:ss.SSSS'
                    ],
                    'userId' => [
                        'type' => 'integer'
                    ],
                    'assetId' => [
                        'type' => 'integer'
                    ],
                    'channelId' => [
                        'type' => 'integer'
                    ],
                    'listingId' => [
                        'type' => 'integer'
                    ]
                ]
            ]
        ]
    ]
];
