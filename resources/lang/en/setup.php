<?php

return [
    'title' => 'Set up VersionTracker',
    'description' => 'Create the first administrator and configure the basic application settings. This page is available only during the initial setup.',
    'completed' => 'Setup completed for :email. You can now sign in.',
    'fields' => [
        'token' => 'One-time setup token',
        'name' => 'Administrator name',
        'email' => 'Administrator email',
        'password' => 'Password',
        'password_confirmation' => 'Confirm password',
        'application_name' => 'Application name',
        'support_url' => 'Support URL',
        'default_locale' => 'Default language',
        'fallback_locale' => 'Fallback language',
        'mail_from_address' => 'Mail sender address',
        'mail_from_name' => 'Mail sender name',
    ],
    'actions' => [
        'complete' => 'Complete setup',
    ],
];
