<?php

namespace LaravelPropertyBag\tests\Classes;

use Illuminate\Database\Eloquent\Model;
use LaravelPropertyBag\Contracts\HasSettings;
use LaravelPropertyBag\Settings\HasSettings as HasSettingsTrait;

class Post extends Model implements HasSettings
{
    use HasSettingsTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'posts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'body',
        'user_id',
    ];

    /**
     * Settings config class.
     *
     * @var string
     */
    protected $settingsConfig = 'LaravelPropertyBag\tests\Classes\PostConfig';
}
