<?php

namespace App\Domains\CMDB\Models;

use App\Core\Models\BaseModel;
use App\Core\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CIAttribute extends BaseModel
{
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'ci_attributes';

    protected $fillable = [
        'ci_type_id',
        'configuration_item_id',
        'name',
        'code',
        'description',
        'data_type',
        'value',
        'default_value',
        'is_required',
        'is_unique',
        'is_searchable',
        'is_encrypted',
        'validation_rules',
        'options',
        'order_index',
        'group_name',
        'metadata'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_unique' => 'boolean',
        'is_searchable' => 'boolean',
        'is_encrypted' => 'boolean',
        'validation_rules' => 'array',
        'options' => 'array',
        'metadata' => 'array',
        'order_index' => 'integer'
    ];

    protected $attributes = [
        'is_required' => false,
        'is_unique' => false,
        'is_searchable' => false,
        'is_encrypted' => false,
        'order_index' => 0,
        'validation_rules' => '[]',
        'options' => '[]',
        'metadata' => '{}'
    ];

    /**
     * Data type constants
     */
    const TYPE_STRING = 'string';
    const TYPE_TEXT = 'text';
    const TYPE_INTEGER = 'integer';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'datetime';
    const TYPE_TIME = 'time';
    const TYPE_JSON = 'json';
    const TYPE_SELECT = 'select';
    const TYPE_MULTISELECT = 'multiselect';
    const TYPE_EMAIL = 'email';
    const TYPE_URL = 'url';
    const TYPE_IP = 'ip';
    const TYPE_MAC = 'mac';
    const TYPE_FILE = 'file';
    const TYPE_REFERENCE = 'reference'; // Reference to another CI

    /**
     * Get the CI type this attribute belongs to
     */
    public function ciType(): BelongsTo
    {
        return $this->belongsTo(CIType::class, 'ci_type_id');
    }

    /**
     * Get the configuration item this attribute value belongs to
     */
    public function configurationItem(): BelongsTo
    {
        return $this->belongsTo(ConfigurationItem::class, 'configuration_item_id');
    }

    /**
     * Scope to get attributes for a specific CI type
     */
    public function scopeForType($query, $ciTypeId)
    {
        return $query->where('ci_type_id', $ciTypeId);
    }

    /**
     * Scope to get attribute values for a specific CI
     */
    public function scopeForCI($query, $configurationItemId)
    {
        return $query->where('configuration_item_id', $configurationItemId);
    }

    /**
     * Scope to get only searchable attributes
     */
    public function scopeSearchable($query)
    {
        return $query->where('is_searchable', true);
    }

    /**
     * Scope to get only required attributes
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Get available data types
     */
    public static function getDataTypes(): array
    {
        return [
            self::TYPE_STRING => 'String',
            self::TYPE_TEXT => 'Text',
            self::TYPE_INTEGER => 'Integer',
            self::TYPE_DECIMAL => 'Decimal',
            self::TYPE_BOOLEAN => 'Boolean',
            self::TYPE_DATE => 'Date',
            self::TYPE_DATETIME => 'Date & Time',
            self::TYPE_TIME => 'Time',
            self::TYPE_JSON => 'JSON',
            self::TYPE_SELECT => 'Select',
            self::TYPE_MULTISELECT => 'Multi Select',
            self::TYPE_EMAIL => 'Email',
            self::TYPE_URL => 'URL',
            self::TYPE_IP => 'IP Address',
            self::TYPE_MAC => 'MAC Address',
            self::TYPE_FILE => 'File',
            self::TYPE_REFERENCE => 'CI Reference'
        ];
    }

    /**
     * Get the casted value based on data type
     */
    public function getCastedValueAttribute()
    {
        if ($this->value === null) {
            return null;
        }

        switch ($this->data_type) {
            case self::TYPE_INTEGER:
                return (int) $this->value;
            
            case self::TYPE_DECIMAL:
                return (float) $this->value;
            
            case self::TYPE_BOOLEAN:
                return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
            
            case self::TYPE_DATE:
                return Carbon::parse($this->value)->toDateString();
            
            case self::TYPE_DATETIME:
                return Carbon::parse($this->value);
            
            case self::TYPE_TIME:
                return Carbon::parse($this->value)->toTimeString();
            
            case self::TYPE_JSON:
            case self::TYPE_MULTISELECT:
                return json_decode($this->value, true);
            
            default:
                return $this->value;
        }
    }

    /**
     * Set the value with proper casting
     */
    public function setValueAttribute($value)
    {
        if ($value === null) {
            $this->attributes['value'] = null;
            return;
        }

        switch ($this->data_type) {
            case self::TYPE_JSON:
            case self::TYPE_MULTISELECT:
                $this->attributes['value'] = is_string($value) ? $value : json_encode($value);
                break;
            
            case self::TYPE_BOOLEAN:
                $this->attributes['value'] = $value ? '1' : '0';
                break;
            
            default:
                $this->attributes['value'] = (string) $value;
        }
    }

    /**
     * Validate the attribute value
     */
    public function validateValue(): bool
    {
        if ($this->is_required && empty($this->value)) {
            return false;
        }

        // Type-specific validation
        switch ($this->data_type) {
            case self::TYPE_EMAIL:
                return filter_var($this->value, FILTER_VALIDATE_EMAIL) !== false;
            
            case self::TYPE_URL:
                return filter_var($this->value, FILTER_VALIDATE_URL) !== false;
            
            case self::TYPE_IP:
                return filter_var($this->value, FILTER_VALIDATE_IP) !== false;
            
            case self::TYPE_MAC:
                return preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $this->value);
            
            case self::TYPE_SELECT:
                return in_array($this->value, $this->options ?? []);
            
            case self::TYPE_MULTISELECT:
                $values = json_decode($this->value, true) ?? [];
                return empty(array_diff($values, $this->options ?? []));
        }

        return true;
    }

    /**
     * Check if the attribute should be encrypted
     */
    public function shouldEncrypt(): bool
    {
        return $this->is_encrypted;
    }

    /**
     * Get the display value
     */
    public function getDisplayValueAttribute()
    {
        if ($this->is_encrypted && $this->value) {
            return '********';
        }

        if ($this->data_type === self::TYPE_BOOLEAN) {
            return $this->casted_value ? 'Yes' : 'No';
        }

        if ($this->data_type === self::TYPE_REFERENCE && $this->value) {
            return ConfigurationItem::find($this->value)?->name ?? $this->value;
        }

        return $this->casted_value;
    }
}