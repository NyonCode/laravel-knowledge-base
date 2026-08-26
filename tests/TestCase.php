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
        $app['config']->set('database.connections.testing', $this->databaseConnection());
    }

    /**
     * Na čem sada běží — v paměti, dokud si CI neřekne o skutečný server.
     *
     * SQLite v paměti je správná výchozí volba: nic se neinstaluje a sada
     * doběhne za vteřiny. Není to ale to, na čem balíček běží v provozu, a
     * rozdíly jsou skutečné — cizí klíče, citlivost `like` na velikost
     * písmen, chování `coalesce` nad datem. Proto se dá přepnout.
     *
     * Čte se `getenv()`, ne `env()`: proměnnou nastavuje běhové prostředí
     * (GitHub Actions), ne `.env`, a Laravelův `env()` na ni podle
     * `variables_order` vůbec nemusí vidět.
     *
     * @return array<string, mixed>
     */
    protected function databaseConnection(): array
    {
        $driver = getenv('DB_DRIVER') ?: 'sqlite';

        if ($driver === 'sqlite') {
            return [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                // Bez tohohle SQLite cizí klíče **nevynucuje** a `nullOnDelete`
                // se mlčky nestane – testy by pak tvrdily opak toho, co dělá
                // MySQL v provozu.
                'foreign_key_constraints' => true,
            ];
        }

        return [
            'driver' => $driver,
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => getenv('DB_PORT') ?: ($driver === 'pgsql' ? '5432' : '3306'),
            'database' => getenv('DB_DATABASE') ?: 'knowledge_base',
            'username' => getenv('DB_USERNAME') ?: ($driver === 'pgsql' ? 'postgres' : 'root'),
            'password' => getenv('DB_PASSWORD') ?: '',
            'charset' => $driver === 'pgsql' ? 'utf8' : 'utf8mb4',
            'prefix' => '',
        ];
    }
}
