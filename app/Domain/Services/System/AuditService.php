<?php

namespace VistiqCore\Services\System;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuditService
{
    public function record(string $action, string $type, ?int $id, array $before = [], array $after = []): void
    {
        DB::table('audit_logs')->insert(['actor_id' => Auth::id(), 'action' => $action, 'entity_type' => $type, 'entity_id' => $id, 'before' => json_encode($this->redact($before), JSON_UNESCAPED_UNICODE), 'after' => json_encode($this->redact($after), JSON_UNESCAPED_UNICODE), 'created_at' => now()]);
    }

    public function redact(array $value): array
    {
        foreach ($value as $key => $item) {
            if (preg_match('/password|secret|api_key|token|otp|code_hash|^key$/i', (string) $key)) {
                $value[$key] = '[redacted]';
            } elseif (is_array($item)) {
                $value[$key] = $this->redact($item);
            }
        }

        return $value;
    }
}
