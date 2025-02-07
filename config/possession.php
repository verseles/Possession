<?php

return [
    'user_model' => App\Models\User::class,
    'admin_guard' => 'web',
    'session_keys' => [
        'original_user' => 'possession.original_user_id',
    ],
];