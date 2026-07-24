<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    private function forceSqliteMemoryEnv(): void
    {
        putenv('APP_ENV=testing');
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_SERVER['APP_ENV'] = 'testing';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = ':memory:';
    }

    public function createApplication()
    {
        // artisan test / Pest às vezes ignoram force= do phpunit.xml e herdam .env.
        // Forçar ANTES do bootstrap — senão RefreshDatabase (ou migrate) limpa o MySQL real.
        // Também evita bootstrap/cache/config.php com APP_ENV=local (CSRF 419 nos POSTs).
        $this->forceSqliteMemoryEnv();

        $app = parent::createApplication();

        $app['env'] = 'testing';
        $app['config']->set('app.env', 'testing');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('database.connections.sqlite.prefix', '');
        DB::purge();
        DB::reconnect('sqlite');

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSafeTestingDatabase();
        $this->ensureTestingSchema();
    }

    /**
     * Abort if somehow still on the app MySQL — never wipe real data.
     */
    protected function assertSafeTestingDatabase(): void
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(
                "Testes abortados: DB deve ser sqlite :memory: (atual: {$connection}/{$database}). ".
                'Nunca use o MySQL da aplicação nos testes.'
            );
        }
    }

    /**
     * Cria o schema em :memory: com `migrate` (aditivo). Nunca migrate:fresh / wipe.
     */
    protected function ensureTestingSchema(): void
    {
        if (Schema::hasTable('migrations')) {
            return;
        }

        Artisan::call('migrate', ['--force' => true]);
    }
}
