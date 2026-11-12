<?php declare(strict_types=1);

namespace Hihaho\Sdk\Upload;

use Hihaho\Sdk\Data\UploadTicket;
use Hihaho\Sdk\Enums\UploadMethod;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use UnexpectedValueException;

/**
 * Sends a file to the upload URL of a registered upload. The URL is a third-party host, so
 * these requests carry no hihaho token and follow no redirect.
 */
final readonly class FileSender
{
    public const int TUS_CHUNK_BYTES = 10 * 1024 * 1024;

    public function __construct(
        private Factory $http,
        private int $timeoutSeconds = 3600,
    ) {}

    /**
     * The stream must be seekable; the caller owns it.
     */
    public function send(UploadTicket $ticket, StreamInterface $stream, int $size): void
    {
        match ($ticket->uploadMethod) {
            UploadMethod::Axios, UploadMethod::Upchunk => $this->put($ticket, $stream),
            UploadMethod::Tus => $this->tus($ticket, $stream, $size),
            null => throw new UnexpectedValueException("Upload method {$this->methodName($ticket)} is not supported by this SDK version."),
        };
    }

    /**
     * The presigned URL is signed against the content type, so it is sent exactly as returned.
     */
    private function put(UploadTicket $ticket, StreamInterface $stream): void
    {
        $stream->rewind();

        $request = $this->request()->withBody($stream, $ticket->contentType);

        // A content type of one space still sends the header; an empty one sends no header.
        if ($ticket->contentType === '') {
            $request = $request->withRequestMiddleware(fn (RequestInterface $psrRequest): RequestInterface => $psrRequest->withoutHeader('Content-Type'));
        }

        $this->successful($request->put($ticket->url));
    }

    /**
     * The upload already exists on the tus server, so only HEAD and PATCH are needed.
     * A HEAD first reads the offset, so an interrupted send resumes where it stopped.
     */
    private function tus(UploadTicket $ticket, StreamInterface $stream, int $size): void
    {
        $offset = $this->tusOffset($ticket->url);

        if ($offset > $size) {
            throw new RuntimeException("The upload server already holds {$offset} bytes, more than the file's {$size} bytes.");
        }

        while ($offset < $size) {
            $stream->seek($offset);
            $chunk = $stream->read(min(self::TUS_CHUNK_BYTES, $size - $offset));

            if ($chunk === '') {
                throw new RuntimeException("Cannot read the file at byte {$offset}.");
            }

            $response = $this->successful($this->request()
                ->withHeaders(['Tus-Resumable' => '1.0.0', 'Upload-Offset' => (string) $offset])
                ->withBody($chunk, 'application/offset+octet-stream')
                ->patch($ticket->url));

            $next = $this->uploadOffset($response);

            if ($next <= $offset) {
                throw new RuntimeException("The upload server did not move past byte {$offset}.");
            }

            $offset = $next;
        }
    }

    private function tusOffset(string $url): int
    {
        return $this->uploadOffset($this->successful($this->request()
            ->withHeaders(['Tus-Resumable' => '1.0.0'])
            ->head($url)));
    }

    /**
     * tus 1.0 requires `Upload-Offset` on every HEAD and successful PATCH response.
     */
    private function uploadOffset(Response $response): int
    {
        $offset = $response->header('Upload-Offset');

        if (! ctype_digit($offset)) {
            throw new UnexpectedValueException('The upload server sent no valid Upload-Offset header.');
        }

        return (int) $offset;
    }

    /**
     * Redirects are off, so a 3xx fails here too instead of passing as done.
     */
    private function successful(Response $response): Response
    {
        if (! $response->successful()) {
            throw new RequestException($response);
        }

        return $response;
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->createPendingRequest()
            ->withOptions(['allow_redirects' => false])
            ->timeout($this->timeoutSeconds);
    }

    private function methodName(UploadTicket $ticket): string
    {
        return is_scalar($ticket->uploadMethodValue) ? (string) $ticket->uploadMethodValue : 'unknown';
    }
}
