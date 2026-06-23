<?php

return [
    'greeting' => 'Hallo :name,',
    'team' => 'Team',
    'thanks' => 'Vielen Dank für die Nutzung des Software Version Trackers.',
    'view_version' => 'Version anzeigen',
    'view_vulnerability' => 'Sicherheitslücke anzeigen',
    'version_approved' => [
        'subject' => 'Version :version wurde genehmigt',
        'body' => 'Die Version :version von :software wurde genehmigt und kann veröffentlicht werden.',
    ],
    'security_alert' => [
        'subject' => 'Sicherheitswarnung für :cve',
        'body' => 'Neue Sicherheitslücke :cve (:severity) in :software :version entdeckt.',
    ],
    'lifecycle_alert' => [
        'subject' => ':software :version erreicht bald EOL',
        'body' => ':software :version erreicht am :date das Ende des Lebenszyklus.',
    ],
    'fix_available' => [
        'subject' => 'Fix für :cve verfügbar',
        'body' => 'Für :cve in :software ist eine behobene Version (:version) verfügbar.',
    ],
];
