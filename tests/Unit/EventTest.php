<?php

namespace LaravelPropertyBag\tests\Unit;

use Illuminate\Support\Facades\Event;
use LaravelPropertyBag\Events\SettingReset;
use LaravelPropertyBag\Events\SettingUpdated;
use LaravelPropertyBag\tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class EventTest extends TestCase
{
    #[Test]
    public function creating_a_setting_dispatches_setting_updated_with_was_created_true(): void
    {
        Event::fake([SettingUpdated::class, SettingReset::class]);

        $this->user->settings()->set(['test_settings1' => 'bananas']);

        Event::assertDispatched(SettingUpdated::class, function (SettingUpdated $event): bool {
            return $event->resource->is($this->user)
                && $event->key === 'test_settings1'
                && $event->oldValue === 'monkey'
                && $event->newValue === 'bananas'
                && $event->wasCreated === true;
        });

        Event::assertNotDispatched(SettingReset::class);
    }

    #[Test]
    public function updating_an_existing_setting_dispatches_setting_updated_with_was_created_false(): void
    {
        $this->user->settings()->set(['test_settings1' => 'bananas']);

        Event::fake([SettingUpdated::class, SettingReset::class]);

        $this->user->settings()->set(['test_settings1' => 'grapes']);

        Event::assertDispatched(SettingUpdated::class, function (SettingUpdated $event): bool {
            return $event->resource->is($this->user)
                && $event->key === 'test_settings1'
                && $event->oldValue === 'bananas'
                && $event->newValue === 'grapes'
                && $event->wasCreated === false;
        });

        Event::assertNotDispatched(SettingReset::class);
    }

    #[Test]
    public function re_setting_an_existing_setting_to_the_same_value_does_not_dispatch_setting_updated(): void
    {
        $this->user->settings()->set(['test_settings1' => 'bananas']);

        Event::fake([SettingUpdated::class, SettingReset::class]);

        $this->user->settings()->set(['test_settings1' => 'bananas']);

        Event::assertNotDispatched(SettingUpdated::class);
        Event::assertNotDispatched(SettingReset::class);
    }

    #[Test]
    public function resetting_a_setting_to_default_dispatches_setting_reset(): void
    {
        $this->user->settings()->set(['test_settings1' => 'bananas']);

        Event::fake([SettingUpdated::class, SettingReset::class]);

        $this->user->settings()->reset('test_settings1');

        Event::assertDispatched(SettingReset::class, function (SettingReset $event): bool {
            return $event->resource->is($this->user)
                && $event->key === 'test_settings1'
                && $event->oldValue === 'bananas'
                && $event->defaultValue === 'monkey';
        });

        Event::assertNotDispatched(SettingUpdated::class);
    }
}
