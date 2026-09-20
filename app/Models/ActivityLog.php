<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{

    protected $fillable = [
        'user_id', 'user_name', 'action', 'module',
        'description', 'model_type', 'model_id',
        'ip_address', 'user_agent', 'old_values', 'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Dashboard "Recent Activity" link target, if any. Only every actually
     * logged with a model reference today are Patient, Invoice, Department,
     * and ShiftHandover (confirmed by grepping every ActivityLog::log() call
     * site), so this intentionally covers just those rather than guessing a
     * generic polymorphic-to-route mapping for types that don't occur.
     * Returns null (row stays plain text, not a broken link) when there's no
     * model reference (e.g. deletions) or the record no longer exists.
     *
     * Recent Activity is only ever rendered for super_admin/admin (see
     * dashboard.blade.php), both of which already have access to every
     * route below, so no additional role check is needed here.
     */
    public function getLinkUrlAttribute(): ?string
    {
        if (!$this->model_type || !$this->model_id) {
            return null;
        }

        return match ($this->model_type) {
            \App\Models\Patient::class       => \App\Models\Patient::find($this->model_id) ? route('patients.show', $this->model_id) : null,
            \App\Models\Invoice::class       => \App\Models\Invoice::find($this->model_id) ? route('billing.invoices.show', $this->model_id) : null,
            \App\Models\Department::class    => \App\Models\Department::find($this->model_id) ? route('settings.departments') : null,
            \App\Models\ShiftHandover::class => \App\Models\ShiftHandover::find($this->model_id) ? route('shift-handover.show', $this->model_id) : null,
            default => null,
        };
    }

    public static function log(
        string $action,
        string $module,
        string $description,
        $model = null,
        array $oldValues = [],
        array $newValues = []
    ) {
        static::create([
            'user_id'     => auth()->id(),
            'user_name'   => auth()->user()?->name ?? 'System',
            'action'      => $action,
            'module'      => $module,
            'description' => $description,
            'model_type'  => $model ? get_class($model) : null,
            'model_id'    => $model?->id,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'old_values'  => $oldValues ?: null,
            'new_values'  => $newValues ?: null,
        ]);
    }
}
