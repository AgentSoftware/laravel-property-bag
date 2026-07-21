<?php

use LaravelPropertyBag\Settings\PropertyBag;

return [

    /*
    |--------------------------------------------------------------------------
    | Settings Namespace
    |--------------------------------------------------------------------------
    |
    | By default, LaravelPropertyBag looks for resource settings config and
    | rules classes in a "Settings" namespace relative to your application's
    | root namespace (e.g. "App\Settings\UserSettings"). Uncomment and set
    | this value to use a custom namespace instead.
    |
    */

    // 'namespace' => 'MyApp\\Settings',

    /*
    |--------------------------------------------------------------------------
    | PropertyBag Model
    |--------------------------------------------------------------------------
    |
    | By default, LaravelPropertyBag stores settings using its own PropertyBag
    | Eloquent model. Set this value to your own model class (extending
    | LaravelPropertyBag\Settings\PropertyBag) to override the model used to
    | read and write property bag rows.
    |
    */

    'model' => PropertyBag::class,

];
