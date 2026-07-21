<?php

namespace LaravelPropertyBag\Settings;

use Illuminate\Database\Eloquent\Model;

class PropertyBag extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'property_bag';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
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
