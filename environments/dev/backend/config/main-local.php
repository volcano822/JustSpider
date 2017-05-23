<?php

$config = [
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => '',
        ],
    ],
    'log' => [
        'traceLevel' => YII_DEBUG ? 3 : 0,
        'targets' => [
            [
                'class' => 'yii\log\TimeFileTarget',
                'levels' => ['error', 'warning'],
                'logFile' => '@app/../logs/backend/hxf.log.wf',
                'logVars' => [],
                'categories' => ['stock',],
            ],
            [
                'class' => 'yii\log\TimeFileTarget',
                'levels' => ['trace', 'info'],
                'logFile' => '@app/../logs/backend/hxf.log',
                'logVars' => [],
                'categories' => ['stock',],
            ],
        ],
    ],
];

if (!YII_ENV_TEST) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'allowedIPs' => ['*'],
    ];
}

return $config;
