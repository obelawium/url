<?php

declare(strict_types=1);

namespace Obelaw\Ium\Url\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Obelaw\Ium\Url\Providers\URLServiceProvider;
use Obelaw\Ium\Engine\GlobalConfigManager;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        // Set the database connection in Obelawium's config manager
        GlobalConfigManager::set('obelawium.db.connection', 'testbench');

        parent::setUp();

        // Run package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            URLServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Setup default database to use sqlite in-memory
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}
