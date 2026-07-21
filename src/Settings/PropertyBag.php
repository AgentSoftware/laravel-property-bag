<?php

namespace LaravelPropertyBag\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

/**
 * Note: $value is annotated as `string`, not the `array` its cast declares, because
 * Settings::valueToJson() already json_encode()s the value before assignment; the
 * 'array' cast's own setter then json_encode()s that string again, so reading it
 * back through the cast (and the manual json_decode() in
 * Settings::getAllSettingsFlat()) always yields a JSON-encoded string, never an
 * array. This is a pre-existing behaviour, not something introduced by this typing
 * pass.
 *
 * @property string $key
 * @property string $value
 */
class PropertyBag extends Model
{
    /**
     * The table associated with the model.
     *
     * Note: left untyped natively - Illuminate\Database\Eloquent\Model
     * declares this property without a type, and PHP property overrides
     * must match the parent's type exactly (including "no type").
     *
     * @var string|null
     */
    protected $table = 'property_bag';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
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
     *
     * @return class-string<self>
     */
    public static function resolveModel(): string
    {
        $model = Config::get('property_bag.model');

        // Config::get() is statically typed to return mixed; the string return type here
        // is enforced natively by PHP at runtime, so a misconfigured non-string
        // value already fails fast with a TypeError rather than being silently
        // coerced. The truthy check preserves the original `?:` semantics.
        // @phpstan-ignore return.type, ternary.condNotBoolean
        return $model ? $model : self::class;
    }
}
