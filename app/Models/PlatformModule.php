<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Super Admin Feature Management catalog entry — see
 * SUPER_ADMIN_FEATURE_MANAGEMENT_REPORT.md. Metadata + dependency graph
 * only; the real enabled/disabled state for independently-toggleable
 * entries lives on `HospitalSetting::features_enabled` (see
 * FeatureManagementService::isEnabled()).
 */
class PlatformModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'key', 'name', 'description', 'category', 'icon', 'color',
        'tier', 'visibility', 'depends_on', 'sort_order',
    ];

    protected $casts = [
        'depends_on' => 'array',
    ];

    /**
     * Route-bound by `key` (e.g. "lab"), not `id` — the Feature Catalog UI's
     * detail/enable/disable actions all address modules by their human-
     * readable key (used consistently in URLs, JS, and audit descriptions),
     * not the numeric primary key.
     */
    public function getRouteKeyName(): string
    {
        return 'key';
    }

    public function isCore(): bool
    {
        return $this->tier === 'core';
    }

    public function isExperimental(): bool
    {
        return $this->tier === 'experimental';
    }

    /**
     * Whether this catalog entry has its own independent toggle in
     * `features_enabled` — some entries (e.g. Radiology, Blood Bank) are
     * deliberately *derived*: their availability follows a dependency's
     * flag rather than having a second, redundant switch of their own.
     */
    public function isIndependentlyToggleable(array $featuresEnabled): bool
    {
        return ! $this->isCore() && array_key_exists($this->key, $featuresEnabled);
    }
}
