<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogService
{
    public function log(
        string $module,
        string $documentType,
        int $documentId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): ActivityLog {
        $request = app(Request::class);

        return ActivityLog::create([
            'user_id' => auth()->id(),
            'module' => $module,
            'document_type' => $documentType,
            'document_id' => $documentId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    public function logCreate(string $module, string $documentType, int $documentId, array $data): ActivityLog
    {
        return $this->log($module, $documentType, $documentId, 'created', null, $data);
    }

    public function logUpdate(string $module, string $documentType, int $documentId, array $oldData, array $newData): ActivityLog
    {
        return $this->log($module, $documentType, $documentId, 'updated', $oldData, $newData);
    }

    public function logDelete(string $module, string $documentType, int $documentId): ActivityLog
    {
        return $this->log($module, $documentType, $documentId, 'deleted');
    }

    public function logRestore(string $module, string $documentType, int $documentId): ActivityLog
    {
        return $this->log($module, $documentType, $documentId, 'restored');
    }

    public function logAction(string $module, string $documentType, int $documentId, string $action): ActivityLog
    {
        return $this->log($module, $documentType, $documentId, $action);
    }
}
