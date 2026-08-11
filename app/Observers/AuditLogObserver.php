<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogObserver
{
    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        $this->logActivity($model, 'CREATED');
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        $this->logActivity($model, 'UPDATED');
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->logActivity($model, 'DELETED');
    }

    /**
     * Record activity to audit_logs table.
     */
    protected function logActivity(Model $model, string $action): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => (string) $model->getKey(),
            'ip_address' => app()->runningInConsole() ? 'SYSTEM' : (request()->ip() ?? 'UNKNOWN'),
        ]);
    }
}
