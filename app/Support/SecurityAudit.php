<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/** Audit metadata only; never accept request bodies or model serialization. */
class SecurityAudit
{
    public static function record(string $event, ?int $targetId = null, string $result = 'success', array $metadata = []): void
    {
        $request = app()->bound('request') ? request() : null;
        Log::channel(config('security.audit_channel', 'audit'))->info($event, array_merge([
            'actor_id' => Auth::id(),
            'target_id' => $targetId,
            'result' => $result,
            'ip' => $request?->ip(),
        ], array_intersect_key($metadata, array_flip(['identifier_hash', 'changed_fields', 'exception_type', 'http_status']))));
    }
}
