<?php

namespace LaravelPropertyBag\tests\Unit;

use LaravelPropertyBag\Settings\PropertyBag;
use LaravelPropertyBag\tests\Classes\CustomPropertyBag;
use LaravelPropertyBag\tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PropertyBagModelTest extends TestCase
{
    #[Test]
    public function default_property_bag_model_is_used_when_config_is_unset(): void
    {
        $this->assertEquals(PropertyBag::class, PropertyBag::resolveModel());

        $this->user->settings(['test_settings1' => 'bananas']);

        $record = $this->user->propertyBag()->first();

        $this->assertInstanceOf(PropertyBag::class, $record);
    }

    #[Test]
    public function custom_property_bag_model_is_used_when_configured(): void
    {
        config(['property_bag.model' => CustomPropertyBag::class]);

        $this->assertEquals(CustomPropertyBag::class, PropertyBag::resolveModel());

        $this->user->settings(['test_settings1' => 'bananas']);

        $record = $this->user->propertyBag()->first();

        $this->assertInstanceOf(CustomPropertyBag::class, $record);

        $this->assertDatabaseHas('property_bag', [
            'resource_id' => $this->user->id,
            'resource_type' => 'LaravelPropertyBag\tests\Classes\User',
            'key' => 'test_settings1',
            'value' => json_encode('["bananas"]'),
        ]);
    }
}
