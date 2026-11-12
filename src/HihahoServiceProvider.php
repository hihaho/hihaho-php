<?php declare(strict_types=1);

namespace Hihaho\Sdk;

use Hihaho\Sdk\Http\RetryPolicy;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory;
use Override;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class HihahoServiceProvider extends PackageServiceProvider
{
    #[Override]
    public function configurePackage(Package $package): void
    {
        $package
            ->name('hihaho')
            ->hasConfigFile();
    }

    #[Override]
    public function packageRegistered(): void
    {
        $this->app->singleton(Hihaho::class, fn (Application $app): Hihaho => new Hihaho(
            token: config()->string('hihaho.token'),
            baseUrl: self::baseUrl(),
            http: $app->make(Factory::class),
            retryPolicy: new RetryPolicy(
                maxRetries: config()->integer('hihaho.retries.max', 3),
                retryRateLimited: config()->boolean('hihaho.retries.rate_limited', true),
                retryServerErrorsOnGet: config()->boolean('hihaho.retries.server_errors_on_get', true),
            ),
            timeoutSeconds: config()->integer('hihaho.timeout', 30),
        ));
    }

    /**
     * @internal
     */
    public static function baseUrl(): string
    {
        $baseUrl = config('hihaho.base_url');

        return is_string($baseUrl) && $baseUrl !== '' ? $baseUrl : Hihaho::DEFAULT_BASE_URL;
    }
}
