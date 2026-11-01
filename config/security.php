<?php

return [
    'audit_channel' => env('AUDIT_LOG_CHANNEL', 'audit'),
    'reset_expire_minutes' => (int) env('PASSWORD_RESET_EXPIRE_MINUTES', 60),
];
