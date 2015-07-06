<?php

namespace SebastianBerc\Repositories\Test;

use Faker\Factory as Faker;
use Laracasts\TestDummy\Factory as TestDummy;

/**
 * Class TestCase
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Test
 */
abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /**
     * Clean up the testing environment before the next test.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @return \Faker\Generator
     */
    public function fake()
    {
        return (new Faker())->create();
    }

    /**
     * Return the new TestDummy instance.
     *
     * @return TestDummy
     */
    public function factory()
    {
        return new TestDummy();
    }

    /**
     * Setup the testing sqlite database in memory.
     */
    private function setUpDatabase()
    {
        $app['path.base'] = __DIR__ . '/..';

        $this->app['config']->set('cache.default', 'array');
        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite.database', ':memory:');

        // Relative to 'vendor/orchestra/testbench/fixture'
        $migratePath = '../../../../tests/migrations';

        /** @var \Illuminate\Contracts\Console\Kernel $artisan */
        $artisan = $this->app->make('Illuminate\Contracts\Console\Kernel');
        $artisan->call('migrate', [
            '--database' => 'sqlite',
            '--path'     => $migratePath,
        ]);
    }
}
