<?php

return [
    'trial_days' => (int) env('HR_TRIAL_DAYS', 14),
    'trial_plan' => env('HR_TRIAL_PLAN', 'standard'),
    'trial_fallback_plan' => env('HR_TRIAL_FALLBACK_PLAN', 'basic'),
];
