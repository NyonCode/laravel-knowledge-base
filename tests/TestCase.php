<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Livewire\LivewireServiceProvider;
use NyonCode\KnowledgeBase\Providers\KnowledgeBaseServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $model) => 'NyonCode\\KnowledgeBase\\Database\\Factories\\'
                .class_basename($model).'Factory'
        );

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /** @return array<int, class-string> */
    protected function getPackageProviders($app): array
    {
        return [KnowledgeBaseServiceProvider::class, LivewireServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        // Livewire renderuje komponentu se šifrovaným snapshotem, takže
        // testbench bez klíče spadne až uvnitř pohledu.
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            // Bez tohohle SQLite cizí klíče **nevynucuje** a `nullOnDelete`
            // se mlčky nestane – testy by pak tvrdily opak toho, co dělá
            // MySQL v provozu.
            'foreign_key_constraints' => true,
        ]);
    }
}
