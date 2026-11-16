<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Audit Log
    |--------------------------------------------------------------------------
    |
    | Audit records are immutable through the application UI. The retention
    | period below is enforced by the `audit:prune` scheduled command.
    |
    */

    'enabled' => env('AUDIT_LOG_ENABLED', true),

    'retention_days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 365),

    'prune_chunk_size' => 500,

    'max_depth' => 4,

    'max_value_length' => 5000,

    'user_agent_limit' => 500,

];
