<?php

namespace LaravelPropertyBag\tests\Classes;

use Illuminate\Database\Eloquent\Model;
use LaravelPropertyBag\Contracts\HasSettings;
use LaravelPropertyBag\Settings\HasSettings as HasSettingsTrait;

class Comment extends Model implements HasSettings
{
    use HasSettingsTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'comments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'body',
    ];

    /**
     * Settings config class.
     *
     * @var string
     */
    protected $settingsConfig = 'LaravelPropertyBag\tests\Classes\CommentConfig';
}
