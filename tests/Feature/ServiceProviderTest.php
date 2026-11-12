<?php declare(strict_types=1);

namespace Hihaho\Sdk\Tests\Feature;

use Hihaho\Sdk\Facades\Hihaho as HihahoFacade;
use Hihaho\Sdk\Hihaho;
use Hihaho\Sdk\HihahoServiceProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

final class ServiceProviderTest extends TestCase
{
    #[Test]
    public function it_builds_the_client_from_config(): void
    {
        config()->set('hihaho.token', 'config-token');
        config()->set('hihaho.base_url', 'https://api.hihaho.test');
        HihahoFacade::fake(['*' => Http::response(['data' => ['id' => 12]])]);

        resolve(Hihaho::class)->video(12)->get();

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.hihaho.test/v2/video/12'
            && $request->hasHeader('Authorization', 'Bearer config-token'));
    }

    #[Test]
    public function fake_works_for_a_client_resolved_before_it(): void
    {
        config()->set('hihaho.token', 'config-token');
        Http::preventStrayRequests();
        $hihaho = resolve(Hihaho::class);
        $this->assertSame($hihaho, HihahoFacade::getFacadeRoot());

        HihahoFacade::fake(['*' => Http::response(['data' => [['id' => 7, 'name' => 'Training']]])]);

        $this->assertSame(7, $hihaho->folders()->list()->page()->items[0]->id);
        $this->assertSame(7, HihahoFacade::folders()->list()->page()->items[0]->id);
    }

    #[Test]
    public function fake_scopes_a_relative_pattern_to_the_base_url(): void
    {
        config()->set('hihaho.token', 'config-token');
        config()->set('hihaho.base_url', 'https://api.hihaho.test');
        Http::preventStrayRequests();
        HihahoFacade::fake(['v2/video/*' => Http::response(['data' => ['id' => 12]])]);

        $this->assertSame(12, HihahoFacade::video(12)->get()->id);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.hihaho.test/v2/video/12');
    }

    #[Test]
    public function fake_does_not_fake_other_hosts(): void
    {
        Http::preventStrayRequests();
        HihahoFacade::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('https://example.com');

        Http::get('https://example.com');
    }

    #[Test]
    public function an_empty_token_throws_when_the_client_is_resolved(): void
    {
        config()->set('hihaho.token', '');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('HIHAHO_API_TOKEN');

        resolve(Hihaho::class);
    }

    #[Test]
    public function fake_sets_a_placeholder_token_when_none_is_configured(): void
    {
        config()->set('hihaho.token', '');
        HihahoFacade::fake(['*' => Http::response(['data' => ['id' => 12]])]);

        resolve(Hihaho::class)->video(12)->get();

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer fake-token'));
    }

    #[Test]
    public function an_empty_base_url_falls_back_to_the_default(): void
    {
        config()->set('hihaho.token', 'config-token');
        config()->set('hihaho.base_url', '');
        HihahoFacade::fake(['*' => Http::response(['data' => ['id' => 12]])]);

        resolve(Hihaho::class)->video(12)->get();

        Http::assertSent(fn (Request $request): bool => $request->url() === Hihaho::DEFAULT_BASE_URL . '/v2/video/12');
    }

    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [HihahoServiceProvider::class];
    }
}
