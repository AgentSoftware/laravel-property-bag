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
    public function publish_user_command_rejects_an_invalid_resource_argument(): void
    {
        $exitCode = Artisan::call('pbag:make', ['resource' => '../../evil']);

        $this->assertNotSame(0, $exitCode);

        $this->assertDirectoryDoesNotExist(app_path('Settings'));
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

    #[Test]
    public function publish_user_command_reports_failure_and_exits_non_zero_when_settings_file_cannot_be_written(): void
    {
        // Pre-create 'Settings' as a plain file (not a directory), so
        // makeDir() sees it as already existing and skips mkdir, but the
        // subsequent file_put_contents() into "Settings/UserSettings.php"
        // fails because "Settings" is not a directory.
        File::put(app_path('Settings'), 'not a directory');

        $exitCode = Artisan::call('pbag:make', ['resource' => 'User']);

        $this->assertNotSame(0, $exitCode);

        $output = Artisan::output();

        $this->assertStringContainsString('Unable to write', $output);
        $this->assertStringNotContainsString('successfully created', $output);

        $this->assertFileDoesNotExist(app_path('Settings/UserSettings.php'));

        File::delete(app_path('Settings'));
    }

    #[Test]
    public function publish_rules_file_command_reports_failure_and_exits_non_zero_when_rules_file_cannot_be_written(): void
    {
        // Pre-create 'Settings/Resources' as a plain file (not a directory),
        // so makeDir() sees it as already existing and skips mkdir, but the
        // subsequent file_put_contents() into "Resources/Rules.php" fails
        // because "Resources" is not a directory.
        File::makeDirectory(app_path('Settings'));
        File::put(app_path('Settings/Resources'), 'not a directory');

        $exitCode = Artisan::call('pbag:rules');

        $this->assertNotSame(0, $exitCode);

        $output = Artisan::output();

        $this->assertStringContainsString('Unable to write', $output);
        $this->assertStringNotContainsString('successfully created', $output);

        $this->assertFileDoesNotExist(app_path('Settings/Resources/Rules.php'));

        File::delete(app_path('Settings/Resources'));
        File::deleteDirectory(app_path('Settings'));
    }

    #[Test]
    public function publish_rules_file_command_throws_when_settings_directory_cannot_be_created(): void
    {
        // Pre-create 'Settings' as a plain file (not a directory). makeDir('Settings')
        // sees it as already existing and skips mkdir, but makeDir('Settings/Resources')
        // then fails because its parent path segment is a file, not a directory -
        // exercising makeDir()'s own RuntimeException rather than the write-failure
        // path above.
        File::put(app_path('Settings'), 'not a directory');

        try {
            Artisan::call('pbag:rules');

            $this->fail('Expected a RuntimeException to be thrown.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Unable to create directory', $e->getMessage());
        } finally {
            File::delete(app_path('Settings'));
        }
    }
}
