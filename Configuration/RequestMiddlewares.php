<?php

return [
    'backend' => [
        'de-swebhosting-typo3-extension/logout-info/backend-session-data-collector' => [
            'target' => \Sto\LogoutInfo\Middleware\BackendSessionDataCollectorMiddleware::class,
            'before' => ['typo3/cms-backend/authentication'],
        ],
    ],
    'frontend' => [
        'de-swebhosting-typo3-extension/logout-info/backend-session-data-collector' => [
            'target' => \Sto\LogoutInfo\Middleware\BackendSessionDataCollectorMiddleware::class,
            'before' => ['typo3/cms-frontend/backend-user-authentication'],
        ],
    ],
];
