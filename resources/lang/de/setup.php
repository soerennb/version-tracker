<?php

return [
    'title' => 'VersionTracker einrichten',
    'description' => 'Lege die erste Administration an und konfiguriere die grundlegenden Anwendungseinstellungen. Diese Seite ist nur während der Ersteinrichtung verfügbar.',
    'completed' => 'Die Einrichtung für :email ist abgeschlossen. Du kannst dich jetzt anmelden.',
    'fields' => [
        'token' => 'Einmaliger Setup-Token',
        'name' => 'Name der Administration',
        'email' => 'E-Mail der Administration',
        'password' => 'Passwort',
        'password_confirmation' => 'Passwort bestätigen',
        'application_name' => 'Name der Anwendung',
        'support_url' => 'Support-URL',
        'default_locale' => 'Standardsprache',
        'fallback_locale' => 'Fallback-Sprache',
        'mail_from_address' => 'Absenderadresse',
        'mail_from_name' => 'Absendername',
    ],
    'actions' => [
        'complete' => 'Einrichtung abschließen',
    ],
];
