<?php

namespace LaravelPropertyBag\tests\Unit;

use Illuminate\Support\Facades\Event;
use LaravelPropertyBag\Events\SettingReset;
use LaravelPropertyBag\Events\SettingUpdated;
use LaravelPropertyBag\Settings\PropertyBag;
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

    #[Test]
    public function creating_a_setting_throws_and_does_not_dispatch_event_when_persist_fails(): void
    {
        Event::fake([SettingUpdated::class, SettingReset::class]);

        PropertyBag::saving(fn (): bool => false);

        try {
            $this->user->settings()->set(['test_settings1' => 'bananas']);

            $this->fail('Expected a RuntimeException to be thrown.');
        } catch (\RuntimeException $e) {
            // Expected: the failed save() must throw before any event fires.
        } finally {
            PropertyBag::flushEventListeners();
        }

        Event::assertNotDispatched(SettingUpdated::class);
        Event::assertNotDispatched(SettingReset::class);
    }

    #[Test]
    public function updating_a_setting_throws_and_does_not_dispatch_event_when_persist_fails(): void
    {
        $this->user->settings()->set(['test_settings1' => 'bananas']);

        Event::fake([SettingUpdated::class, SettingReset::class]);

        PropertyBag::saving(fn (): bool => false);

        try {
            $this->user->settings()->set(['test_settings1' => 'grapes']);

            $this->fail('Expected a RuntimeException to be thrown.');
        } catch (\RuntimeException $e) {
            // Expected: the failed save() must throw before any event fires.
        } finally {
            PropertyBag::flushEventListeners();
        }

        Event::assertNotDispatched(SettingUpdated::class);
        Event::assertNotDispatched(SettingReset::class);
    }

    #[Test]
    public function resetting_a_setting_throws_and_does_not_dispatch_event_when_delete_fails(): void
    {
        $this->user->settings()->set(['test_settings1' => 'bananas']);

        Event::fake([SettingUpdated::class, SettingReset::class]);

        PropertyBag::deleting(fn (): bool => false);

        try {
            $this->user->settings()->reset('test_settings1');

            $this->fail('Expected a RuntimeException to be thrown.');
        } catch (\RuntimeException $e) {
            // Expected: the failed delete() must throw before any event fires.
        } finally {
            PropertyBag::flushEventListeners();
        }

        Event::assertNotDispatched(SettingUpdated::class);
        Event::assertNotDispatched(SettingReset::class);
    }
}
