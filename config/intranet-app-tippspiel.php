<?php

return [
    'gvp_model' => env('INTRANET_APP_TIPPSPIEL_GVP_MODEL', \App\Models\Gvp::class),
    'user_model' => env('INTRANET_APP_TIPPSPIEL_USER_MODEL', \App\Models\User::class),

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
