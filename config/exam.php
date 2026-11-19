<?php

return [
    'sse_enabled' => (bool) env('EXAM_SSE_ENABLED', false),
    'heartbeat_seconds' => (int) env('EXAM_HEARTBEAT_SECONDS', 5),
    'uncertain_seconds' => (int) env('EXAM_UNCERTAIN_SECONDS', 10),
    'offline_seconds' => (int) env('EXAM_OFFLINE_SECONDS', 20),
    'retention_days' => (int) env('EXAM_RETENTION_DAYS', 180),
    // Keep keys server-side. Configure the same settings in the institution's SEB client.
    'seb_config_keys' => array_filter(explode(',', env('SEB_CONFIG_KEYS', ''))),
];
