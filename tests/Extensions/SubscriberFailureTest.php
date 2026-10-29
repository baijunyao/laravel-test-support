<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Tests\Extensions;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class SubscriberFailureTest extends TestCase
{
    public function testMissingDatabaseConfigurationExitsWithFailure(): void
    {
        $this->assertFailureExit(<<<'PHP'
            $app = new Illuminate\Foundation\Application();
            $app->instance('config', new Illuminate\Config\Repository());
            $event = (new ReflectionClass(PHPUnit\Event\TestRunner\Finished::class))->newInstanceWithoutConstructor();
            (new Baijunyao\LaravelTestSupport\Extensions\FinishedSubscriber($app, 'test_'))->notify($event);
            PHP, 'Config error.');
    }

    public function testDatabaseCreationFailureExitsWithFailure(): void
    {
        $this->assertFailureExit(<<<'PHP'
            $app = new Illuminate\Foundation\Application();
            $app->instance('db', new class {
                public function getSchemaBuilder() { throw new RuntimeException('test failure'); }
            });
            $event = (new ReflectionClass(PHPUnit\Event\TestRunner\Started::class))->newInstanceWithoutConstructor();
            (new Baijunyao\LaravelTestSupport\Extensions\StartedSubscriber($app, '/unused/', 'test_'))->notify($event);
            PHP, 'Create database error:');
    }

    private function assertFailureExit(string $script, string $message): void
    {
        $process = new Process([PHP_BINARY, '-r', 'require $argv[1]; ' . $script, dirname(__DIR__, 2) . '/vendor/autoload.php']);
        $process->run();

        $this->assertSame(1, $process->getExitCode(), $process->getErrorOutput());
        $this->assertStringContainsString($message, $process->getOutput());
    }
}
