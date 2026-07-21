<?php

namespace LaravelPropertyBag\tests\Classes;

use Illuminate\Foundation\Auth\User as BaseUser;
use LaravelPropertyBag\Contracts\HasSettings;
use LaravelPropertyBag\Settings\HasSettings as HasSettingsTrait;

class Admin extends BaseUser implements HasSettings
{
    use HasSettingsTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
}
