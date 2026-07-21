<?php

namespace LaravelPropertyBag\tests\Classes;

use Illuminate\Database\Eloquent\Model;
use LaravelPropertyBag\Contracts\HasSettings;
use LaravelPropertyBag\Settings\HasSettings as HasSettingsTrait;

class Group extends Model implements HasSettings
{
    use HasSettingsTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'groups';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'type',
        'max_members',
    ];

    /**
     * Settings config class.
     *
     * @var string
     */
    protected $settingsConfig = 'LaravelPropertyBag\tests\Classes\GroupConfig';
}
