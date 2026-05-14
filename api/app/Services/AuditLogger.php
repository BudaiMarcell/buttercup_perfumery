<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{

    public static function log(
        string $action,
        string $modelType,
        ?int $modelId = null,
        ?string $description = null,
        ?array $changes = null
    ): AuditLog {
        $user = Auth::user();

        return AuditLog::create([
            'user_id'     => $user?->id,
            'user_name'   => $user?->name ?? 'Ismeretlen',
            'action'      => $action,
            'model_type'  => $modelType,
            'model_id'    => $modelId,
            'description' => $description,
            'changes'     => $changes,
        ]);
    }
}
