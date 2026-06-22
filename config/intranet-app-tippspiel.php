<?php

return [
    'roles' => [
        'admin' => [
            'name' => 'App-Tippspiel-Admin',
            'permissions' => [
                'see-app-tippspiel',
                'manage-app-tippspiel',
            ],
        ],
        'user' => [
            'name' => 'App-Tippspiel-Benutzer',
            'permissions' => [
                'see-app-tippspiel',
            ],
            'all_users' => true,
        ],
    ],
];
