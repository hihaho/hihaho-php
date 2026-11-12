<?php declare(strict_types=1);

namespace Hihaho\Sdk\Resources;

use Carbon\CarbonImmutable;
use DateInterval;
use GuzzleHttp\Psr7\Utils;
use Hihaho\Sdk\Data\Upload;
use Hihaho\Sdk\Data\UploadSummary;
use Hihaho\Sdk\Data\UploadTicket;
use Hihaho\Sdk\Data\Video;
use Hihaho\Sdk\Exceptions\HihahoException;
use Hihaho\Sdk\Exceptions\UploadException;
use Hihaho\Sdk\Exceptions\ValidationException;
use Hihaho\Sdk\Http\Connector;
use Hihaho\Sdk\Pagination\ListRequest;
use Hihaho\Sdk\Upload\FileSender;
use Hihaho\Sdk\Upload\ResumeStep;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Sleep;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final readonly class Uploads extends Resource
{
    public function __construct(Connector $connector, private FileSender $fileSender)
    {
        parent::__construct($connector);
    }

    /**
     * Every API, transport or upload-host failure after registering throws `UploadException` with the
     * upload id and the ticket; its docblock says when to continue the upload and when to register a
     * new one. A path is opened and closed by the SDK; a resource or a stream stays the caller's and
     * is never closed. Without a name, the SDK uses the file name of the path, or of the local file
     * behind the resource or stream.
     * It returns the upload right after finishing, so the video can still be processing; pass it to
     * `wait()` to wait until the video platform has processed it.
     *
     * @param  string|open-resource|StreamInterface  $file  a path, or a seekable resource or stream with a known size
     * @param  list<int>  $replaceVideoIds  videos whose source this upload replaces
     */
    public function upload(
        int $folderId,
        mixed $file,
        ?string $name = null,
        ?int $durationMs = null,
        array $replaceVideoIds = [],
    ): Upload {
        [$stream, $size] = $this->open($file);

        try {
            $name ??= $this->nameOf($file, $stream);
            $ticket = $this->register($folderId, $name, $size, $durationMs, $replaceVideoIds);
            $this->sendStream($ticket, $stream, $size);
        } finally {
            $this->release($file, $stream);
        }

        return $this->finishAndRead($ticket);
    }

    /**
     * Continues an upload that `upload()` could not complete, from the step that failed: it sends the
     * file again when the send failed, then finishes and reads the upload. A tus upload resumes from the
     * bytes the upload host already has. Throws `InvalidArgumentException` when the upload failed on
     * the server or the video platform; check `canResume()` first and register a new upload instead.
     *
     * @param  string|open-resource|StreamInterface  $file  the same file, as a path or a seekable resource or stream with a known size
     */
    public function resume(UploadException $exception, mixed $file): Upload
    {
        $ticket = $exception->ticket;
        $step = $exception->resumeFrom();

        if (! $ticket instanceof UploadTicket || ! $step instanceof ResumeStep) {
            throw new InvalidArgumentException("Upload {$exception->uploadId} cannot be resumed. Register a new upload.", 0, $exception);
        }

        return match ($step) {
            ResumeStep::Send => $this->sendAndComplete($ticket, $file),
            ResumeStep::Finish => $this->finishAndRead($ticket),
            ResumeStep::Status => $this->read($ticket),
        };
    }

    /**
     * @param  string|open-resource|StreamInterface  $file
     */
    private function sendAndComplete(UploadTicket $ticket, mixed $file): Upload
    {
        $this->send($ticket, $file);

        return $this->finishAndRead($ticket);
    }

    private function finishAndRead(UploadTicket $ticket): Upload
    {
        try {
            $this->finish($ticket->id);
        } catch (ValidationException $validationException) {
            throw UploadException::failedOnServer($ticket, $validationException);
        } catch (HihahoException $hihahoException) {
            throw UploadException::finishFailed($ticket, $hihahoException);
        }

        return $this->read($ticket);
    }

    private function read(UploadTicket $ticket): Upload
    {
        try {
            return $this->get($ticket->id);
        } catch (HihahoException $hihahoException) {
            throw UploadException::statusFailed($ticket, $hihahoException);
        }
    }

    /**
     * Sends the file of a registered upload, for example to continue after a crash.
     * A tus upload resumes from the bytes the upload host already has; other methods send the whole file again.
     *
     * @param  string|open-resource|StreamInterface  $file  a path, or a seekable resource or stream with a known size
     */
    public function send(UploadTicket $ticket, mixed $file): void
    {
        [$stream, $size] = $this->open($file);

        try {
            $this->sendStream($ticket, $stream, $size);
        } finally {
            $this->release($file, $stream);
        }
    }

    /**
     * Polls `GET v2/upload-video/{upload}` until the video platform has processed the video or failed.
     * An unknown status keeps polling until the timeout. A whole number is seconds.
     */
    public function wait(Upload|int $upload, DateInterval|int $timeout = 600, DateInterval|int $interval = 5): Upload
    {
        $uploadId = $upload instanceof Upload ? $upload->id : $upload;
        $timeoutSeconds = $this->seconds($timeout);
        $intervalSeconds = $this->seconds($interval);

        if ($intervalSeconds <= 0 || $timeoutSeconds < 0) {
            throw new InvalidArgumentException('Pass an interval above 0 seconds and a timeout of 0 seconds or more.');
        }

        $waited = 0;

        while (true) {
            $upload = $this->get($uploadId);

            if ($upload->isComplete()) {
                return $upload;
            }

            if ($upload->isFailed()) {
                throw UploadException::processingFailed($uploadId);
            }

            if ($waited >= $timeoutSeconds) {
                throw UploadException::timedOut($uploadId, $timeoutSeconds);
            }

            Sleep::for($intervalSeconds)->seconds();
            $waited += $intervalSeconds;
        }
    }

    private function seconds(DateInterval|int $interval): int
    {
        return is_int($interval) ? $interval : CarbonImmutable::parse('@0')->add($interval)->getTimestamp();
    }

    /**
     * @param  string|open-resource|StreamInterface  $file
     * @return array{StreamInterface, int}
     */
    private function open(mixed $file): array
    {
        if (is_string($file)) {
            $handle = is_file($file) ? fopen($file, 'rb') : false;

            if ($handle === false) {
                throw new InvalidArgumentException("Cannot read {$file}.");
            }

            $file = Utils::streamFor($handle);
        } elseif (is_resource($file)) {
            $file = Utils::streamFor($file);
        } elseif (! $file instanceof StreamInterface) {
            throw new InvalidArgumentException('Pass a path, a stream resource or a StreamInterface.');
        }

        $size = $file->getSize();

        if (! $file->isSeekable() || $size === null) {
            throw new InvalidArgumentException('The stream must be seekable and have a known size.');
        }

        return [$file, $size];
    }

    /**
     * @param  string|open-resource|StreamInterface  $file
     */
    private function nameOf(mixed $file, StreamInterface $stream): string
    {
        $path = is_string($file) ? $file : $stream->getMetadata('uri');

        if (! is_string($path) || ! is_file($path)) {
            throw new InvalidArgumentException('Pass a name to upload a stream that is not a local file.');
        }

        return basename($path);
    }

    /**
     * Closes a stream the SDK opened. A resource the caller passed is only detached, because the
     * wrapper would close it on destruction.
     *
     * @param  string|open-resource|StreamInterface  $file
     */
    private function release(mixed $file, StreamInterface $stream): void
    {
        if (is_string($file)) {
            $stream->close();
        } elseif (is_resource($file)) {
            $stream->detach();
        }
    }

    private function sendStream(UploadTicket $ticket, StreamInterface $stream, int $size): void
    {
        try {
            $this->fileSender->send($ticket, $stream, $size);
        } catch (HttpClientException|RuntimeException $exception) {
            throw UploadException::sendFailed($ticket, $exception);
        }
    }

    /**
     * @return ListRequest<UploadSummary>
     */
    public function list(int $folderId): ListRequest
    {
        return $this->paged("v2/video-container/{$folderId}/upload-video", [], UploadSummary::fromArray(...), fn (UploadSummary $upload): int => $upload->id);
    }

    /**
     * Returns where and how to send the file; `upload()` does the whole flow. Send the real byte
     * size: some video platforms require it.
     *
     * @param  list<int>  $replaceVideoIds  videos whose source this upload replaces
     */
    public function register(int $folderId, ?string $name = null, ?int $size = null, ?int $durationMs = null, array $replaceVideoIds = []): UploadTicket
    {
        $body = array_filter([
            'name' => $name,
            'size' => $size,
            'duration_in_ms' => $durationMs,
            'replace_video_ids' => $replaceVideoIds === [] ? null : array_values($replaceVideoIds),
        ], fn (mixed $value): bool => $value !== null);

        return UploadTicket::fromArray($this->data($this->connector->post("v2/video-container/{$folderId}/upload-video", $body)));
    }

    /**
     * Call it after the file is sent; safe to repeat. A 409 means another call is still finishing
     * the upload; a 422 means the upload failed, so register a new upload.
     */
    public function finish(int $uploadId): void
    {
        $this->connector->post("v2/upload-video/{$uploadId}/finished");
    }

    /**
     * The video of a finished upload. Safe to repeat.
     */
    public function createVideo(int $uploadId): Video
    {
        return Video::fromArray($this->data($this->connector->post("v2/upload-video/{$uploadId}/video")));
    }

    /**
     * Removes the folder's failed uploads.
     */
    public function deleteFailed(int $folderId): void
    {
        $this->connector->delete("v2/video-container/{$folderId}/upload-video/failed");
    }

    public function get(int $uploadId): Upload
    {
        return Upload::fromArray($this->getData("v2/upload-video/{$uploadId}"));
    }
}
