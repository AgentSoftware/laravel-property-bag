<?php

namespace LaravelPropertyBag\Settings;

use Illuminate\Database\Eloquent\Model;

class PropertyBag extends Model
{
    /**
     * The table associated with the model.
     *
     * Note: left untyped natively - Illuminate\Database\Eloquent\Model
     * declares this property without a type, and PHP property overrides
     * must match the parent's type exactly (including "no type").
     *
     * @var string
     */
    protected $table = 'property_bag';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'array',
    ];

    /**
     * Resolve the model class used to store property bag records, allowing
     * consumers to override the default via the `property_bag.model` config.
     */
    public static function resolveModel(): string
    {
        return config('property_bag.model') ?: self::class;
    }
}
