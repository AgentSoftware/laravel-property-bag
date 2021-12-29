<?php

namespace LaravelPropertyBag\tests\Unit\Helpers;

use LaravelPropertyBag\Helpers\NameResolver;
use LaravelPropertyBag\tests\Classes\User;
use LaravelPropertyBag\tests\TestCase;

class NameResolverTest extends TestCase
{
    public function test_can_make_config_filename_if_no_user_namespace_config_set(): void
    {
        $namespace = NameResolver::makeConfigFileName(User::class);
        $this->assertEquals('App\Settings\LaravelPropertyBag\tests\Classes\UserSettings', $namespace);
    }

    public function test_can_make_config_filename_if_user_namespace_config_set(): void
    {
        config([
            'property_bag.namespace' => 'MyApp\\Settings'
        ]);

        $namespace = NameResolver::makeConfigFileName(User::class);
        $this->assertEquals('MyApp\Settings\LaravelPropertyBag\tests\Classes\UserSettings', $namespace);
    }

    public function test_can_make_rules_filename_if_no_user_namespace_config_set(): void
    {
        $namespace = NameResolver::makeRulesFileName();
        $this->assertEquals('App\Settings\Resources\Rules', $namespace);
    }

    public function test_can_make_rules_filename_if_user_namespace_config_set(): void
    {
        config([
            'property_bag.namespace' => 'MyApp\\Settings'
        ]);

        $namespace = NameResolver::makeRulesFileName();
        $this->assertEquals('MyApp\Settings\Resources\Rules', $namespace);
    }
}
