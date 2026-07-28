<?php

namespace LaravelPropertyBag\tests\Classes;

use Illuminate\Foundation\Auth\User as BaseUser;
use LaravelPropertyBag\Settings\HasSettings;

class Admin extends BaseUser
{
    use HasSettings;

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
