<?php declare(strict_types=1);

namespace Hihaho\Sdk\Tests\Unit\Resources;

use Closure;
use DateInterval;
use Error;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Utils;
use Hihaho\Sdk\Data\UploadTicket;
use Hihaho\Sdk\Enums\UploadVideoStatus;
use Hihaho\Sdk\Exceptions\ServerException;
use Hihaho\Sdk\Exceptions\UploadException;
use Hihaho\Sdk\Exceptions\ValidationException;
use Hihaho\Sdk\Upload\FileSender;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Sleep;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

final class UploadTest extends ResourceTestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        Sleep::fake();
        $this->file = (string) tempnam(sys_get_temp_dir(), 'video');
        file_put_contents($this->file, str_repeat('v', 1000));
    }

    protected function tearDown(): void
    {
        Sleep::fake(false);
        @unlink($this->file);

        parent::tearDown();
    }

    #[Test]
    #[DataProvider('putProvider')]
    public function a_put_upload_sends_the_file_then_finishes_and_waits(string $method, string $contentType, ?string $expectedHeader): void
    {
        $sentBody = null;
        $this->http->fake([
            'api.hihaho.com/v2/video-container/7/upload-video' => Factory::response(['data' => $this->ticket($method, $contentType)]),
            'upload.example.com/*' => function (Request $request) use (&$sentBody) {
                $sentBody = $request->body();

                return Factory::response('', 200);
            },
            'api.hihaho.com/v2/upload-video/9001/finished' => Factory::response('', 204),
            'api.hihaho.com/v2/upload-video/9001' => $this->http->sequence()
                ->push(['data' => $this->upload(UploadVideoStatus::InProgress)])
                ->push(['data' => $this->upload(UploadVideoStatus::InProgress)])
                ->push(['data' => $this->upload(UploadVideoStatus::Ready)]),
        ]);

        $upload = $this->hihaho->uploads()->wait($this->hihaho->uploads()
            ->upload(7, $this->file, 'Safety.mp4'));

        $this->assertSame(UploadVideoStatus::Ready, $upload->status);
        $this->http->assertSent(fn (Request $request): bool => $request->url() === 'https://api.hihaho.com/v2/video-container/7/upload-video'
            && $request['size'] === 1000 && $request['name'] === 'Safety.mp4');
        $this->http->assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && $request->url() === 'https://upload.example.com/file'
            && ! $request->hasHeader('Authorization')
            && ($expectedHeader === null ? ! $request->hasHeader('Content-Type') : $request->header('Content-Type') === [$expectedHeader]));
        $this->assertSame(str_repeat('v', 1000), $sentBody);
        $this->http->assertSent(fn (Request $request): bool => $request->url() === 'https://api.hihaho.com/v2/upload-video/9001/finished');
        Sleep::assertSleptTimes(1);
    }

    /**
     * @return iterable<string, array{string, string, string|null}>
     */
    public static function putProvider(): iterable
    {
        yield 'jw player keeps an empty header' => ['axios', ' ', ''];
        yield 'panopto sends no content type' => ['axios', '', null];
        yield 'mux single put' => ['upchunk', 'video/mp4', 'video/mp4'];
    }

    #[Test]
    public function a_tus_upload_resumes_from_the_server_offset_in_chunks(): void
    {
        $chunk = FileSender::TUS_CHUNK_BYTES;
        file_put_contents($this->file, str_repeat('a', $chunk) . str_repeat('b', 10));

        $this->http->fake(function (Request $request) use ($chunk) {
            if ($request->method() === 'HEAD') {
                return Factory::response('', 200, ['Upload-Offset' => (string) $chunk, 'Tus-Resumable' => '1.0.0']);
            }

            return Factory::response('', 204, ['Upload-Offset' => (string) ($chunk + 10)]);
        });

        $this->hihaho->uploads()->send(UploadTicket::fromArray($this->ticket('tus', '')), $this->file);

        $this->http->assertSentCount(2);
        $this->http->assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
            && $request->header('Upload-Offset') === [(string) $chunk]
            && $request->header('Content-Type') === ['application/offset+octet-stream']
            && $request->body() === str_repeat('b', 10));
    }

    #[Test]
    public function a_tus_upload_sends_a_file_larger_than_one_chunk_in_parts(): void
    {
        $chunk = FileSender::TUS_CHUNK_BYTES;
        file_put_contents($this->file, str_repeat('a', $chunk + 5));
        $offsets = [];

        $this->http->fake(function (Request $request) use (&$offsets) {
            if ($request->method() === 'HEAD') {
                return Factory::response('', 200, ['Upload-Offset' => '0']);
            }

            $header = $request->header('Upload-Offset')[0] ?? '0';
            $offset = is_string($header) ? $header : '0';
            $offsets[] = $offset;

            return Factory::response('', 204, ['Upload-Offset' => (string) ((int) $offset + strlen($request->body()))]);
        });

        $this->hihaho->uploads()->send(UploadTicket::fromArray($this->ticket('tus', '')), $this->file);

        $this->assertSame(['0', (string) $chunk], $offsets);
    }

    #[Test]
    public function an_offset_larger_than_the_file_throws_with_the_upload_id(): void
    {
        $this->http->fake(fn () => Factory::response('', 200, ['Upload-Offset' => '5000']));

        try {
            $this->hihaho->uploads()->send(UploadTicket::fromArray($this->ticket('tus', '')), $this->file);
            self::fail('No exception was thrown.');
        } catch (UploadException $uploadException) {
            $this->assertSame(9001, $uploadException->uploadId);
        }
    }

    #[Test]
    public function a_stream_is_uploaded_and_left_open_for_the_caller(): void
    {
        $sentBody = null;
        $this->http->fake([
            'api.hihaho.com/v2/video-container/7/upload-video' => Factory::response(['data' => $this->ticket('axios', 'video/mp4')]),
            'upload.example.com/*' => function (Request $request) use (&$sentBody) {
                $sentBody = $request->body();

                return Factory::response('', 200);
            },
            'api.hihaho.com/v2/upload-video/9001/finished' => Factory::response('', 204),
            'api.hihaho.com/v2/upload-video/9001' => Factory::response(['data' => $this->upload(UploadVideoStatus::Ready)]),
        ]);
        $handle = fopen($this->file, 'rb');
        $this->assertIsResource($handle);

        $this->hihaho->uploads()->upload(7, Utils::streamFor($handle), 'Safety.mp4');

        $this->assertSame(str_repeat('v', 1000), $sentBody);
        $this->http->assertSent(fn (Request $request): bool => $request->url() === 'https://api.hihaho.com/v2/video-container/7/upload-video'
            && $request['size'] === 1000 && $request['name'] === 'Safety.mp4');
        $this->assertIsResource($handle, 'The SDK closed a stream the caller owns.');
        fclose($handle);
    }

    #[Test]
    public function a_resource_is_uploaded_under_its_file_name_and_left_open_for_the_caller(): void
    {
        $sentBody = null;
        $this->http->fake([
            'api.hihaho.com/v2/video-container/7/upload-video' => Factory::response(['data' => $this->ticket('axios', 'video/mp4')]),
            'upload.example.com/*' => function (Request $request) use (&$sentBody) {
                $sentBody = $request->body();

                return Factory::response('', 200);
            },
            'api.hihaho.com/v2/upload-video/9001/finished' => Factory::response('', 204),
            'api.hihaho.com/v2/upload-video/9001' => Factory::response(['data' => $this->upload(UploadVideoStatus::Ready)]),
        ]);
        $handle = fopen($this->file, 'rb');
        $this->assertIsResource($handle);

        $this->hihaho->uploads()->upload(7, $handle);

        $this->assertSame(str_repeat('v', 1000), $sentBody);
        $this->http->assertSent(fn (Request $request): bool => $request->url() === 'https://api.hihaho.com/v2/video-container/7/upload-video'
            && $request['size'] === 1000 && $request['name'] === basename($this->file));
        $this->assertIsResource($handle, 'The SDK closed a resource the caller owns.');
        fclose($handle);
    }

    /**
     * @param  Closure(string): (string|StreamInterface)  $file
     */
    #[Test]
    #[DataProvider('unusableFileProvider')]
    public function an_unusable_file_throws_before_anything_is_sent(Closure $file, ?string $name): void
    {
        $this->respond(['data' => $this->ticket('axios', 'video/mp4')]);

        try {
            $this->hihaho->uploads()->upload(7, $file($this->file), $name);
            self::fail('No exception was thrown.');
        } catch (InvalidArgumentException) {
            $this->http->assertNothingSent();
        }
    }

    /**
     * @return iterable<string, array{Closure(string): (string|StreamInterface), string|null}>
     */
    public static function unusableFileProvider(): iterable
    {
        yield 'missing path' => [fn (string $file): string => $file . '-missing', null];
        yield 'stream that cannot seek' => [fn (string $file): StreamInterface => new NoSeekStream(Utils::streamFor(Utils::tryFopen($file, 'rb'))), 'a.mp4'];
        yield 'stream of unknown size' => [fn (string $file): StreamInterface => FnStream::decorate(Utils::streamFor(Utils::tryFopen($file, 'rb')), ['getSize' => fn (): ?int => null]), 'a.mp4'];
        yield 'stream that is not a local file, without a name' => [fn (string $file): StreamInterface => Utils::streamFor(str_repeat('v', 1000)), null];
    }

    #[Test]
    public function a_failed_send_throws_with_the_upload_id_and_does_not_finish(): void
    {
        $this->http->fake([
            'api.hihaho.com/v2/video-container/7/upload-video' => Factory::response(['data' => $this->ticket('axios', 'video/mp4')]),
            'upload.example.com/*' => Factory::response('Forbidden', 403),
            'api.hihaho.com/*' => Factory::response('', 204),
        ]);

        try {
            $this->hihaho->uploads()->upload(7, $this->file);
            self::fail('No exception was thrown.');
        } catch (UploadException $uploadException) {
            $this->assertSame(9001, $uploadException->uploadId);
            $this->http->assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/finished'));
        }
    }

    #[Test]
    public function the_ticket_of_a_failed_send_lets_the_caller_resume_the_upload(): void
    {
        $this->http->fake([
            'api.hihaho.com/v2/video-container/7/upload-video' => Factory::response(['data' => $this->ticket('axios', 'video/mp4')]),
            'upload.example.com/*' => $this->http->sequence()
                ->push('Service Unavailable', 503)
                ->push('', 200),
            'api.hihaho.com/v2/upload-video/9001/finished' => Factory::response('', 204),
            'api.hihaho.com/v2/upload-video/9001' => Factory::response(['data' => $this->upload(UploadVideoStatus::Ready)]),
        ]);

        try {
            $this->hihaho->uploads()->upload(7, $this->file);
            self::fail('No exception was thrown.');
        } catch (UploadException $uploadException) {
            $ticket = $uploadException->ticket;
            $this->assertInstanceOf(UploadTicket::class, $ticket);

            $this->hihaho->uploads()->send($ticket, $this->file);
            $this->hihaho->uploads()->finish($ticket->id);
        }

        $this->assertSame(UploadVideoStatus::Ready, $this->hihaho->uploads()->wait(9001)->status);
        $this->http->assertSentCount(5);
    }

    #[Test]
    public function resume_sends_the_file_again_after_a_failed_send(): void
    {
        $this->http->fake([
            'api.hihaho.com/v2/video-container/7/upload-video' => Factory::response(['data' => $this->ticket('axios', 'video/mp4')]),
            'upload.example.com/*' => $this->http->sequence()
                ->push('Service Unavailable', 503)
                ->push('', 200),
            'api.hihaho.com/v2/upload-video/9001/finished' => Factory::response('', 204),
            'api.hihaho.com/v2/upload-video/9001' => Factory::response(['data' => $this->upload(UploadVideoStatus::Ready)]),
        ]);

        $exception = $this->failedUpload();

        $this->assertTrue($exception->canResume());
        $this->assertTrue($this->hihaho->uploads()->resume($exception, $this->file)->isComplete());
        $this->http->assertSentCount(5);
    }

    #[Test]
    public function resume_after_a_failed_finish_does_not_send_the_file_again(): void
    {
        $this->http->fake([
            'api.hihaho.com/v2/video-container/7/upload-video' => Factory::response(['data' => $this->ticket('axios', 'video/mp4')]),
            'upload.example.com/*' => Factory::response('', 200),
            'api.hihaho.com/v2/upload-video/9001/finished' => $this->http->sequence()
                ->push(['message' => 'Server Error'], 500)
                ->push('', 204),
            'api.hihaho.com/v2/upload-video/9001' => Factory::response(['data' => $this->upload(UploadVideoStatus::InProgress)]),
        ]);

        $upload = $this->hihaho->uploads()->resume($this->failedUpload(), $this->file);

        $this->assertFalse($upload->isComplete());
        $this->http->assertSentCount(5);
        $this->assertCount(1, $this->http->recorded(fn (Request $request): bool => str_starts_with($request->url(), 'https://upload.example.com')));
    }

    #[Test]
    public function resume_after_a_failed_status_read_only_reads_the_upload(): void
    {
        $this->http->fake([
            'api.hihaho.com/v2/video-container/7/upload-video' => Factory::response(['data' => $this->ticket('axios', 'video/mp4')]),
            'upload.example.com/*' => Factory::response('', 200),
            'api.hihaho.com/v2/upload-video/9001/finished' => Factory::response('', 204),
            'api.hihaho.com/v2/upload-video/9001' => $this->http->sequence()
                ->push(['message' => 'Server Error'], 500)
                ->push(['data' => $this->upload(UploadVideoStatus::Ready)]),
        ]);

        $upload = $this->hihaho->uploads()->resume($this->failedUpload(), $this->file);

        $this->assertTrue($upload->isComplete());
        $this->http->assertSentCount(5);
        $this->assertCount(1, $this->http->recorded(fn (Request $request): bool => str_ends_with($request->url(), '/finished')));
    }

    /**
     * @param  array<string, mixed>  $responses
     */
    #[Test]
    #[DataProvider('unresumableProvider')]
    public function resume_refuses_an_upload_that_failed_for_good(array $responses): void
    {
        $this->http->fake([
            'api.hihaho.com/v2/video-container/7/upload-video' => Factory::response(['data' => $this->ticket('axios', 'video/mp4')]),
            'upload.example.com/*' => Factory::response('', 200),
            ...$responses,
        ]);
        $exception = $this->failedUpload();
        $sent = count($this->http->recorded());

        $this->assertFalse($exception->canResume());

        try {
            $this->hihaho->uploads()->resume($exception, $this->file);
            self::fail('No exception was thrown.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            $this->assertSame('Upload 9001 cannot be resumed. Register a new upload.', $invalidArgumentException->getMessage());
            $this->assertSame($exception, $invalidArgumentException->getPrevious());
            $this->assertCount($sent, $this->http->recorded());
        }
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function unresumableProvider(): iterable
    {
        yield 'finish rejected with 422' => [[
            'api.hihaho.com/v2/upload-video/9001/finished' => Factory::response(['message' => 'This upload failed.'], 422),
        ]];
    }

    #[Test]
    public function a_standalone_wait_that_times_out_cannot_be_resumed(): void
    {
        $this->http->fake(['api.hihaho.com/v2/upload-video/9001' => Factory::response(['data' => $this->upload(UploadVideoStatus::InProgress)])]);

        try {
            $this->hihaho->uploads()->wait(9001, timeout: 0);
            self::fail('No exception was thrown.');
        } catch (UploadException $uploadException) {
            $this->assertFalse($uploadException->canResume());
        }
    }

    private function failedUpload(): UploadException
    {
        try {
            $this->hihaho->uploads()->upload(7, $this->file);
        } catch (UploadException $uploadException) {
            return $uploadException;
        }

        self::fail('No exception was thrown.');
    }

    #[Test]
    public function an_upload_does_not_wait_for_processing_by_default(): void
    {
        $this->http->fake([
            'api.hihaho.com/v2/video-container/7/upload-video' => Factory::response(['data' => $this->ticket('axios', 'video/mp4')]),
            'upload.example.com/*' => Factory::response('', 200),
            'api.hihaho.com/v2/upload-video/9001/finished' => Factory::response('', 204),
            'api.hihaho.com/v2/upload-video/9001' => Factory::response(['data' => $this->upload(UploadVideoStatus::InProgress)]),
        ]);

        $upload = $this->hihaho->uploads()->upload(7, $this->file);

        $this->assertSame(UploadVideoStatus::InProgress, $upload->status);
        Sleep::assertNeverSlept();
    }

    #[Test]
    public function the_upload_host_redirect_is_not_followed(): void
    {
        $this->http->fake([
            'upload.example.com/*' => Factory::response('', 302, ['Location' => 'https://elsewhere.example.com/file']),
            'elsewhere.example.com/*' => Factory::response('', 200),
        ]);

        try {
            $this->hihaho->uploads()->send(UploadTicket::fromArray($this->ticket('axios', 'video/mp4')), $this->file);
            self::fail('No exception was thrown.');
        } catch (UploadException $uploadException) {
            $this->assertSame(9001, $uploadException->uploadId);
            $this->http->assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'elsewhere.example.com'));
        }
    }

    #[Test]
    #[DataProvider('tusRedirectProvider')]
    public function a_tus_redirect_is_a_failed_send(string $redirectedMethod): void
    {
        $this->http->fake(fn (Request $request) => $request->method() === $redirectedMethod
            ? Factory::response('', 307, ['Location' => 'https://elsewhere.example.com/file'])
            : Factory::response('', $request->method() === 'HEAD' ? 200 : 204, ['Upload-Offset' => $request->method() === 'HEAD' ? '0' : '1000']));

        $this->expectException(UploadException::class);

        $this->hihaho->uploads()->send(UploadTicket::fromArray($this->ticket('tus', '')), $this->file);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function tusRedirectProvider(): iterable
    {
        yield 'head' => ['HEAD'];
        yield 'patch' => ['PATCH'];
    }

    /**
     * @param  array<string, string>  $headHeaders
     * @param  array<string, string>  $patchHeaders
     */
    #[Test]
    #[DataProvider('invalidOffsetProvider')]
    public function a_tus_response_without_a_valid_offset_throws(array $headHeaders, array $patchHeaders): void
    {
        $this->http->fake(fn (Request $request) => $request->method() === 'HEAD'
            ? Factory::response('', 200, $headHeaders)
            : Factory::response('', 204, $patchHeaders));

        $this->expectException(UploadException::class);

        $this->hihaho->uploads()->send(UploadTicket::fromArray($this->ticket('tus', '')), $this->file);
    }

    /**
     * @return iterable<string, array{array<string, string>, array<string, string>}>
     */
    public static function invalidOffsetProvider(): iterable
    {
        yield 'head without an offset' => [[], ['Upload-Offset' => '1000']];
        yield 'head with a text offset' => [['Upload-Offset' => 'none'], ['Upload-Offset' => '1000']];
        yield 'patch without an offset' => [['Upload-Offset' => '0'], []];
        yield 'patch with a text offset' => [['Upload-Offset' => '0'], ['Upload-Offset' => 'none']];
    }

    #[Test]
    #[DataProvider('programmingErrorProvider')]
    public function a_programming_error_during_the_send_is_not_wrapped(Throwable $error): void
    {
        $this->http->fake(fn () => throw $error);

        try {
            $this->hihaho->uploads()->send(UploadTicket::fromArray($this->ticket('axios', 'video/mp4')), $this->file);
            self::fail('No exception was thrown.');
        } catch (Throwable $throwable) {
            $this->assertSame($error, $throwable);
        }
    }

    /**
     * @return iterable<string, array{Throwable}>
     */
    public static function programmingErrorProvider(): iterable
    {
        yield 'invalid argument' => [new InvalidArgumentException('Bad argument.')];
        yield 'error' => [new Error('Call to undefined method.')];
    }

    #[Test]
    public function a_connection_failure_during_the_send_carries_the_upload_id(): void
    {
        $this->http->fake(fn () => throw new ConnectionException('Timed out'));

        try {
            $this->hihaho->uploads()->send(UploadTicket::fromArray($this->ticket('axios', 'video/mp4')), $this->file);
            self::fail('No exception was thrown.');
        } catch (UploadException $uploadException) {
            $this->assertSame(9001, $uploadException->uploadId);
            $this->assertInstanceOf(ConnectionException::class, $uploadException->getPrevious());
        }
    }

    #[Test]
    public function a_tus_server_that_does_not_advance_the_offset_throws(): void
    {
        $patches = 0;
        $this->http->fake(function (Request $request) use (&$patches) {
            if ($request->method() === 'PATCH' && ++$patches > 5) {
                throw new RuntimeException('The send kept patching the same offset.');
            }

            return Factory::response('', $request->method() === 'HEAD' ? 200 : 204, ['Upload-Offset' => '0']);
        });

        try {
            $this->hihaho->uploads()->send(UploadTicket::fromArray($this->ticket('tus', '')), $this->file);
            self::fail('No exception was thrown.');
        } catch (UploadException $uploadException) {
            $this->assertSame(9001, $uploadException->uploadId);
            $this->assertSame(1, $patches);
        }
    }

    #[Test]
    public function an_unknown_upload_method_throws_before_sending(): void
    {
        $this->respond('');

        try {
            $this->hihaho->uploads()->send(UploadTicket::fromArray($this->ticket('carrier-pigeon', '')), $this->file);
            self::fail('No exception was thrown.');
        } catch (UploadException $uploadException) {
            $this->assertInstanceOf(UnexpectedValueException::class, $uploadException->getPrevious());
            $this->assertStringContainsString('carrier-pigeon', $uploadException->getMessage());
            $this->http->assertNothingSent();
        }
    }

    #[Test]
    public function a_failed_finish_call_carries_the_upload_id(): void
    {
        $this->http->fake([
            'api.hihaho.com/v2/video-container/7/upload-video' => Factory::response(['data' => $this->ticket('axios', 'video/mp4')]),
            'upload.example.com/*' => Factory::response('', 200),
            'api.hihaho.com/v2/upload-video/9001/finished' => Factory::response(['message' => 'Server Error'], 500),
        ]);

        try {
            $this->hihaho->uploads()->upload(7, $this->file);
            self::fail('No exception was thrown.');
        } catch (UploadException $uploadException) {
            $this->assertSame(9001, $uploadException->uploadId);
            $this->assertStringContainsString('was sent', $uploadException->getMessage());
        }
    }

    #[Test]
    public function a_finish_rejected_with_422_says_to_register_a_new_upload(): void
    {
        $this->http->fake([
            'api.hihaho.com/v2/video-container/7/upload-video' => Factory::response(['data' => $this->ticket('axios', 'video/mp4')]),
            'upload.example.com/*' => Factory::response('', 200),
            'api.hihaho.com/v2/upload-video/9001/finished' => Factory::response(['message' => 'This upload failed. Register a new upload.'], 422),
        ]);

        try {
            $this->hihaho->uploads()->upload(7, $this->file);
            self::fail('No exception was thrown.');
        } catch (UploadException $uploadException) {
            $this->assertSame(9001, $uploadException->uploadId);
            $this->assertSame('Upload 9001 failed on the server. Register a new upload.', $uploadException->getMessage());
            $this->assertInstanceOf(ValidationException::class, $uploadException->getPrevious());
        }
    }

    #[Test]
    public function a_failed_status_read_after_finish_carries_the_upload_id(): void
    {
        $this->http->fake([
            'api.hihaho.com/v2/video-container/7/upload-video' => Factory::response(['data' => $this->ticket('axios', 'video/mp4')]),
            'upload.example.com/*' => Factory::response('', 200),
            'api.hihaho.com/v2/upload-video/9001/finished' => Factory::response('', 204),
            'api.hihaho.com/v2/upload-video/9001' => Factory::response(['message' => 'Server Error'], 500),
        ]);

        try {
            $this->hihaho->uploads()->upload(7, $this->file);
            self::fail('No exception was thrown.');
        } catch (UploadException $uploadException) {
            $this->assertSame(9001, $uploadException->uploadId);
            $this->assertInstanceOf(ServerException::class, $uploadException->getPrevious());
        }
    }

    #[Test]
    public function a_failure_on_the_video_platform_cannot_be_resumed(): void
    {
        $this->respond(['data' => $this->upload(UploadVideoStatus::PlatformProcessingFailed)]);

        try {
            $this->hihaho->uploads()->wait(9001);
            self::fail('No exception was thrown.');
        } catch (UploadException $uploadException) {
            $this->assertNull($uploadException->getPrevious());
            $this->assertFalse($uploadException->canResume());
            $this->assertSame('Upload 9001 failed on the video platform. Register a new upload.', $uploadException->getMessage());
        }
    }

    #[Test]
    #[DataProvider('invalidPollingProvider')]
    public function invalid_polling_arguments_throw_before_anything_is_sent(int $pollIntervalSeconds, int $timeoutSeconds): void
    {
        $this->respond(['data' => $this->upload(UploadVideoStatus::InProgress)]);

        try {
            $this->hihaho->uploads()->wait(9001, timeout: $timeoutSeconds, interval: $pollIntervalSeconds);
            self::fail('No exception was thrown.');
        } catch (InvalidArgumentException) {
            $this->http->assertNothingSent();
        }
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function invalidPollingProvider(): iterable
    {
        yield 'zero interval' => [0, 600];
        yield 'negative interval' => [-1, 600];
        yield 'negative timeout' => [5, -1];
    }

    #[Test]
    #[DataProvider('terminalStatusProvider')]
    public function polling_ends_at_each_terminal_status(UploadVideoStatus $status, bool $complete): void
    {
        $this->respond(['data' => $this->upload($status)]);

        try {
            $upload = $this->hihaho->uploads()->wait(9001);
            $this->assertTrue($complete, 'A failed status returned instead of throwing.');
            $this->assertSame($status, $upload->status);
        } catch (UploadException $uploadException) {
            $this->assertFalse($complete, 'A complete status threw.');
            $this->assertSame(9001, $uploadException->uploadId);
        }

        Sleep::assertNeverSlept();
    }

    /**
     * @return iterable<string, array{UploadVideoStatus, bool}>
     */
    public static function terminalStatusProvider(): iterable
    {
        yield 'ready' => [UploadVideoStatus::Ready, true];
        yield 'legacy complete' => [UploadVideoStatus::LegacyComplete, true];
        yield 'upload failed or abandoned' => [UploadVideoStatus::UploadFailedOrAbandoned, false];
        yield 'platform processing failed' => [UploadVideoStatus::PlatformProcessingFailed, false];
    }

    #[Test]
    public function polling_stops_at_the_timeout(): void
    {
        $this->respond(['data' => $this->upload(UploadVideoStatus::InProgress)]);

        try {
            $this->hihaho->uploads()->wait(9001, timeout: 15, interval: 5);
            self::fail('No exception was thrown.');
        } catch (UploadException $uploadException) {
            $this->assertStringContainsString('15 seconds', $uploadException->getMessage());
            Sleep::assertSleptTimes(3);
        }
    }

    #[Test]
    public function the_wait_takes_a_date_interval(): void
    {
        $this->respond(['data' => $this->upload(UploadVideoStatus::InProgress)]);

        try {
            $this->hihaho->uploads()->wait(9001, timeout: new DateInterval('PT1M'), interval: new DateInterval('PT20S'));
            self::fail('No exception was thrown.');
        } catch (UploadException $uploadException) {
            $this->assertStringContainsString('60 seconds', $uploadException->getMessage());
            Sleep::assertSleptTimes(3);
        }
    }

    #[Test]
    public function an_unknown_status_keeps_polling(): void
    {
        $this->http->fake(['*' => $this->http->sequence()
            ->push(['data' => [...$this->upload(UploadVideoStatus::Ready), 'status' => 42]])
            ->push(['data' => $this->upload(UploadVideoStatus::Ready)])]);

        $this->assertSame(UploadVideoStatus::Ready, $this->hihaho->uploads()->wait(9001)->status);
    }

    /**
     * @return array<string, mixed>
     */
    private function ticket(string $method, string $contentType): array
    {
        return ['id' => 9001, 'url' => 'https://upload.example.com/file', 'contentType' => $contentType, 'uploadMethod' => $method, 'video' => ['id' => 12, 'uuid' => 'abc']];
    }

    /**
     * @return array<string, mixed>
     */
    private function upload(UploadVideoStatus $status): array
    {
        return ['id' => 9001, 'status' => $status->value, 'status_description' => '', 'uploaded_by' => ['id' => 1, 'name' => 'Ann'], 'video_container' => self::folderRow(), 'videos' => []];
    }
}
