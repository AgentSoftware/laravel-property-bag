<?php

namespace LaravelPropertyBag\tests;

use Hash;
use Illuminate\Foundation\Application;
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
     * @var Collection
     */
    protected $registered;

    /**
     * The default user under test.
     *
     * @var User
     */
    protected $user;

    /**
     * Get package providers.
     *
     * @param  Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param  Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'sqlite');

        $app['config']->set(
            'database.connections.sqlite.database',
            ':memory:'
        );
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../src/Migrations');

        (new CreateUsersTable)->up();

        (new CreateGroupsTable)->up();

        (new CreatePostsTable)->up();

        (new CreateCommentsTable)->up();
    }

    /**
     * Setup DB and test variables before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->makeUser();
    }

    /**
     * Make a user.
     *
     * @param  string  $name
     * @param  string  $password
     * @return User
     */
    protected function makeUser(
        $name = 'Sam Wilson',
        $email = 'samwilson@example.com'
    ) {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('randomstring'),
        ]);
    }

    /**
     * Make an admin user (should fail to get settings).
     *
     * @param  string  $name
     * @param  string  $password
     * @return Admin
     */
    protected function makeAdmin(
        $name = 'Sally Makerson',
        $email = 'sallymakerson@example.com'
    ) {
        return Admin::create([
            'name' => $name,
            'email' => $email,
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
            'name' => 'Laravel User Group',
            'type' => 'tech',
            'max_members' => 20,
        ]);
    }

    /**
     * Make a group.
     *
     * @return Group
     */
    protected function makePost()
    {
        return Post::create([
            'title' => 'Free downloads! Click now!',
            'body' => 'Spammy message in terrible English.',
            'user_id' => 1,
        ]);
    }

    /**
     * Make a group.
     *
     * @return Group
     */
    protected function makeComment()
    {
        return Comment::create([
            'body' => 'Comment body.',
        ]);
    }
}
