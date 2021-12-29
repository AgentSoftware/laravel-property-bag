<?php

namespace LaravelPropertyBag\tests\Unit\Helpers;

use LaravelPropertyBag\Helpers\NameResolver;
use LaravelPropertyBag\tests\Classes\User;
use LaravelPropertyBag\tests\TestCase;
use ReflectionClass;

class NameResolverTest extends TestCase
{
    /** @var string $shortClassName */
    private $shortClassName;

    protected function setUp()
    {
        parent::setUp();

        $reflection = new ReflectionClass(new User());
        $this->shortClassName = $reflection->getShortName();
    }

    public function test_can_make_config_filename_if_no_user_namespace_config_set(): void
    {
        $namespace = NameResolver::makeConfigFileName($this->shortClassName);
        $this->assertEquals('App\Settings\UserSettings', $namespace);
    }

    public function test_can_make_config_filename_if_user_namespace_config_set(): void
    {
        config([
            'property_bag.namespace' => 'MyApp\\Settings'
        ]);

        $namespace = NameResolver::makeConfigFileName($this->shortClassName);
        $this->assertEquals('MyApp\Settings\UserSettings', $namespace);
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
