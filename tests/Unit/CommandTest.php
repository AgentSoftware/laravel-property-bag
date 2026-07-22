<?php

namespace LaravelPropertyBag\tests\Unit;

use Artisan;
use File;
use LaravelPropertyBag\tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CommandTest extends TestCase
{
    #[Test]
    public function publish_user_command_creates_settings_file(): void
    {
        $this->assertFileDoesNotExist(app_path('Settings/UserSettings.php'));

        Artisan::call('pbag:make', ['resource' => 'User']);

        $this->assertFileExists(app_path('Settings/UserSettings.php'));

        File::deleteDirectory(app_path('Settings'));
    }

    #[Test]
    public function published_settings_file_has_correct_namespace(): void
    {
        Artisan::call('pbag:make', ['resource' => 'User']);

        $file = file_get_contents(app_path('Settings/UserSettings.php'));

        $this->assertTrue(strrpos($file, 'namespace App\Settings;') !== false);

        File::deleteDirectory(app_path('Settings'));
    }

    #[Test]
    public function published_settings_file_has_correct_name(): void
    {
        Artisan::call('pbag:make', ['resource' => 'User']);

        $file = file_get_contents(app_path('Settings/UserSettings.php'));

        $this->assertTrue(strrpos($file, 'UserSettings') !== false);

        File::deleteDirectory(app_path('Settings'));
    }

    #[Test]
    public function publish_rules_file_creates_rules_file(): void
    {
        $this->assertFileDoesNotExist(app_path('Settings/Resources/Rules.php'));

        Artisan::call('pbag:rules');

        $this->assertFileExists(app_path('Settings/Resources/Rules.php'));

        File::deleteDirectory(app_path('Settings'));
    }

    #[Test]
    public function publish_user_command_can_be_run_again_when_settings_directory_already_exists(): void
    {
        Artisan::call('pbag:make', ['resource' => 'User']);

        $this->assertFileExists(app_path('Settings'));

        Artisan::call('pbag:make', ['resource' => 'User']);

        $this->assertFileExists(app_path('Settings/UserSettings.php'));

        File::deleteDirectory(app_path('Settings'));
    }

    #[Test]
    public function publish_user_command_capitalizes_a_lowercase_resource_argument(): void
    {
        Artisan::call('pbag:make', ['resource' => 'post']);

        $this->assertFileExists(app_path('Settings/PostSettings.php'));

        $file = file_get_contents(app_path('Settings/PostSettings.php'));

        $this->assertTrue(strrpos($file, 'class PostSettings') !== false);

        File::deleteDirectory(app_path('Settings'));
    }

    #[Test]
    public function publish_rules_file_command_can_be_run_again_when_settings_directories_already_exist(): void
    {
        Artisan::call('pbag:rules');

        $this->assertFileExists(app_path('Settings/Resources'));

        Artisan::call('pbag:rules');

        $this->assertFileExists(app_path('Settings/Resources/Rules.php'));

        File::deleteDirectory(app_path('Settings'));
    }
}
