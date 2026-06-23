<?php

return [
    'greeting' => 'Hello :name,',
    'team' => 'Team',
    'thanks' => 'Thank you for using Software Version Tracker.',
    'view_version' => 'View Version',
    'view_vulnerability' => 'View Vulnerability',
    'version_approved' => [
        'subject' => 'Version :version approved',
        'body' => 'The version :version of :software has been approved and is ready for publishing.',
    ],
    'security_alert' => [
        'subject' => 'Security alert for :cve',
        'body' => 'New vulnerability :cve (:severity) detected in :software :version.',
    ],
    'lifecycle_alert' => [
        'subject' => ':software :version reaches EOL soon',
        'body' => ':software :version reaches end of life on :date.',
    ],
    'fix_available' => [
        'subject' => 'Fix available for :cve',
        'body' => 'A fixed version (:version) is available for :cve in :software.',
    ],
];
