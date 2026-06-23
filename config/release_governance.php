<?php

return [
    'require_four_eyes_for_critical_releases' => (bool) env('RELEASE_GOVERNANCE_REQUIRE_FOUR_EYES_CRITICAL', false),
];
