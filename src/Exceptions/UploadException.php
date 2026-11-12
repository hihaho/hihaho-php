<?php declare(strict_types=1);

namespace Hihaho\Sdk\Exceptions;

use Hihaho\Sdk\Data\UploadTicket;
use Hihaho\Sdk\Upload\ResumeStep;
use Throwable;

/**
 * An upload step failed. `uploadId` identifies the upload. An `upload()` failure sets `ticket`; when
 * `canResume()` is true, `uploads()->resume()` continues from the failed step, and otherwise the
 * upload failed on the server: register a new upload. A `wait()` failure has no ticket: after a
 * timeout call `wait()` again, and after a failure on the video platform register a new upload.
 */
final class UploadException extends HihahoException
{
    public function __construct(
        public readonly int $uploadId,
        string $message,
        ?Throwable $previous = null,
        public readonly ?UploadTicket $ticket = null,
        private readonly ?ResumeStep $resumeFrom = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function sendFailed(UploadTicket $ticket, Throwable $previous): self
    {
        return new self($ticket->id, "Sending the file of upload {$ticket->id} failed: {$previous->getMessage()}", $previous, $ticket, ResumeStep::Send);
    }

    public static function finishFailed(UploadTicket $ticket, Throwable $previous): self
    {
        return new self($ticket->id, "The file of upload {$ticket->id} was sent, but telling hihaho failed: {$previous->getMessage()}", $previous, $ticket, ResumeStep::Finish);
    }

    public static function failedOnServer(UploadTicket $ticket, Throwable $previous): self
    {
        return new self($ticket->id, "Upload {$ticket->id} failed on the server. Register a new upload.", $previous, $ticket);
    }

    public static function statusFailed(UploadTicket $ticket, Throwable $previous): self
    {
        return new self($ticket->id, "Upload {$ticket->id} was finished, but reading its status failed: {$previous->getMessage()}", $previous, $ticket, ResumeStep::Status);
    }

    public static function processingFailed(int $uploadId): self
    {
        return new self($uploadId, "Upload {$uploadId} failed on the video platform. Register a new upload.");
    }

    public static function timedOut(int $uploadId, int $seconds): self
    {
        return new self($uploadId, "Upload {$uploadId} was not processed within {$seconds} seconds. Call wait() again.");
    }

    /**
     * True when `uploads()->resume()` can continue this upload.
     */
    public function canResume(): bool
    {
        return $this->ticket instanceof UploadTicket && $this->resumeFrom instanceof ResumeStep;
    }

    /**
     * @internal
     */
    public function resumeFrom(): ?ResumeStep
    {
        return $this->resumeFrom;
    }
}
