<?php

namespace LaravelPropertyBag\tests;

use Illuminate\Support\Facades\Hash;
use LaravelPropertyBag\ServiceProvider;
use LaravelPropertyBag\tests\Classes\Admin;
use LaravelPropertyBag\tests\Classes\Comment;
use LaravelPropertyBag\tests\Classes\Group;
use LaravelPropertyBag\tests\Classes\Post;
use LaravelPropertyBag\tests\Classes\User;
use LaravelPropertyBag\tests\Migrations\CreateCommentsTable;
use LaravelPropertyBag\tests\Migrations\CreateGroupsTable;
use LaravelPropertyBag\tests\Migrations\CreatePostsTable;
use LaravelPropertyBag\tests\Migrations\CreateUsersTable;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Testing property bag register.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $registered;

    /**
     * Test user.
     *
     * @var User
     */
    protected $user;

    /**
     * Register the package service provider.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    /**
     * Use an in-memory sqlite database for the test app.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');

        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    /**
     * Setup DB and test variables before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();

        $this->user = $this->makeUser();
    }

    /**
     * Run migrations.
     */
    protected function migrate()
    {
        (new CreateUsersTable())->up();

        (new CreateGroupsTable())->up();

        (new CreatePostsTable())->up();

        (new CreateCommentsTable())->up();

        require_once __DIR__.
            '/../src/Migrations/2016_09_19_000000_create_property_bag_table.php';

        $userSettingsTable = 'CreatePropertyBagTable';

        (new $userSettingsTable())->up();
    }

    /**
     * Make a user.
     *
     * @param string $name
     * @param string $email
     *
     * @return User
     */
    protected function makeUser(
        $name = 'Sam Wilson',
        $email = 'samwilson@example.com'
    ) {
        return User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make('randomstring'),
        ]);
    }

    /**
     * Make an admin user (should fail to get settings).
     *
     * @param string $name
     * @param string $email
     *
     * @return Admin
     */
    protected function makeAdmin(
        $name = 'Sally Makerson',
        $email = 'sallymakerson@example.com'
    ) {
        return Admin::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make('randomstring'),
        ]);
    }

    /**
     * Make a group.
     *
     * @return Group
     */
    protected function makeGroup()
    {
        return Group::create([
            'name'        => 'Laravel User Group',
            'type'        => 'tech',
            'max_members' => 20,
        ]);
    }

    /**
     * Make a post.
     *
     * @return Post
     */
    protected function makePost()
    {
        return Post::create([
            'title'   => 'Free downloads! Click now!',
            'body'    => 'Spammy message in terrible English.',
            'user_id' => 1,
        ]);
    }

    /**
     * Make a comment.
     *
     * @return Comment
     */
    protected function makeComment()
    {
        return Comment::create([
            'body' => 'Comment body.',
        ]);
    }
}
