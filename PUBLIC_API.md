# Public API

This file lists the stable public surface of `hihaho/hihaho-php`. A breaking change to anything here needs a major version. Before 1.0, a minor version can break the public API; a patch version cannot. Everything that is not listed here is internal and can change in any release.

## Entry point

`Hihaho\Sdk\Hihaho`

- `new Hihaho(string $token, string $baseUrl = Hihaho::DEFAULT_BASE_URL, ?Factory $http = null, RetryPolicy $retryPolicy = new RetryPolicy(), int $timeoutSeconds = 30)`
- An empty token throws `InvalidArgumentException`.
- The base URL must use `https`. Only `localhost` and `127.0.0.1` may use `http`; any other base URL throws `InvalidArgumentException`.
- Every request sends the User-Agent `hihaho-php/<version> PHP/<PHP version>`.
- `Hihaho::DEFAULT_BASE_URL`
- Accessors: `folders(): Folders`, `uploads(): Uploads`, `videos(): Videos` (the video list and the actions on several videos), and `video(int $videoId): VideoScope` (one video and everything that belongs to it)

`Hihaho\Sdk\Http\RetryPolicy`: `new RetryPolicy(int $maxRetries = 3, bool $retryRateLimited = true, bool $retryServerErrorsOnGet = true, int $initialBackoffSeconds = 1)` and `RetryPolicy::disabled()`. The public readonly properties are `maxRetries`, `retryRateLimited`, `retryServerErrorsOnGet` and `initialBackoffSeconds`.

## Resources

All in `Hihaho\Sdk\Resources`. A list method returns a `ListRequest<T>` (see [Lists](#lists)).

| Class | Methods |
|---|---|
| `Folders` | `list(): ListRequest<Folder>`, `createChild(int $parentId, string $name, string $externalIdentifier, ?Locale $defaultLocale = null, ?bool $requiresTwoFactorAuthentication = null, list<int> $userIds = []): Folder` |
| `Uploads` | `upload(int $folderId, string\|resource\|StreamInterface $file, ?string $name = null, ?int $durationMs = null, list<int> $replaceVideoIds = []): Upload`, `wait(Upload\|int $upload, DateInterval\|int $timeout = 600, DateInterval\|int $interval = 5): Upload`, `register(int $folderId, ?string $name = null, ?int $size = null, ?int $durationMs = null, list<int> $replaceVideoIds = []): UploadTicket`, `resume(UploadException $exception, string\|resource\|StreamInterface $file): Upload`, `send(UploadTicket $ticket, string\|resource\|StreamInterface $file): void`, `finish(int $uploadId): void`, `get(int $uploadId): Upload`, `list(int $folderId): ListRequest<UploadSummary>`, `createVideo(int $uploadId): Video`, `deleteFailed(int $folderId): void` |
| `Videos` | `list(?string $search = null, ?VideoStatus $status = null, ?int $folderId = null): ListRequest<VideoSummary>`, `createFromPlatform(int $folderId, string $platformReference, VideoPlatform $platform): Video`, `deleteMany(list<int> $videoIds): void`, `moveMany(list<int> $videoIds, int $folderId): void`, `swap(string $fromUuid, string $toUuid): SwappedVideos` |
| `VideoScope` | `get(): Video`, `update(VideoUpdate $update): Video`, `export(): VideoExport`, `createViewerToken(string $viewerUid, list<string> $variables = [], ?int $lifetimeSeconds = null): ViewerToken`, and the accessors `interactions(): Interactions`, `questions(): Questions`, `subtitles(): Subtitles`, `thumbnails(): Thumbnails`, `aiTasks(): AiTasks`, `sessions(): Sessions`, `statistics(): Statistics` |
| `Thumbnails` | `list(): ListRequest<PlatformThumbnail>`, `upload(string $imagePath): Video`, `activate(string $thumbnailId): void`, `delete(string $thumbnailId): void` |
| `AiTasks` | `list(): ListRequest<AiTask>`, `start(AiTaskType $type, ?Locale $targetLocale = null, AiTaskOptions $options = new AiTaskOptions()): AiTaskStarted` |
| `Subtitles` | `list(): ListRequest<Subtitle>`, `createMany(list<SubtitleInput> $subtitles): void` |
| `Interactions` | `list(): ListRequest<Interaction>`, `get(int $interactionId): Interaction`, `createMany(list<InteractionInput> $items): list<int>`, `deleteMany(list<int> $interactionIds): void`, `delete(int $interactionId): void` |
| `Questions` | `list(): ListRequest<Question>`, `get(int $questionId): Question`, `createMany(list<QuestionInput> $items): list<int>`, `deleteMany(list<int> $questionIds): void`, `delete(int $questionId): void` |
| `Sessions` | `list(StatisticsFilter $filter = new StatisticsFilter(), PerPage $perPage = PerPage::Hundred): ListRequest<Session>`, `get(int $sessionId): SessionDetail` |
| `Statistics` | `aggregated(): AggregatedStatistics`, `views(StatisticsFilter $filter = new StatisticsFilter()): list<ViewStatistics>`, `viewsByUserAgent(StatisticsFilter $filter = new StatisticsFilter()): array<string, int>`, `interactions(StatisticsFilter $filter = new StatisticsFilter()): ListRequest<InteractionStatistic>`, `questions(StatisticsFilter $filter = new StatisticsFilter()): ListRequest<QuestionStatistic>`, `metadata(StatisticsFilter $filter = new StatisticsFilter(), PerPage $perPage = PerPage::Hundred): ListRequest<SessionMetadata>`, `sessionMetadata(int $sessionId): SessionMetadata` |

Get a resource through its `Hihaho` accessor, or through `video($videoId)` for the resources of one video. Do not construct it yourself: the constructors take internal types.

## Lists

`Hihaho\Sdk\Pagination\ListRequest<T>` implements `IteratorAggregate<int, T>`: `page(int $page = 1): Page<T>`, `all(): LazyCollection<int, T>`, and `getIterator()`, so `foreach` walks every page through `all()`.

`Hihaho\Sdk\Pagination\Page<T>`: the public `items` (`list<T>`), `number` and `meta` properties, `hasMore(): bool` and `nextPage(): ?Page<T>`. `total` and `last_page` in `meta` are not part of the stable contract.

## Request and input classes

- `Hihaho\Sdk\Input`: `VideoUpdate` (`make()`, then `displayName()`, `authorNotes()`, `status()`, `embedding()`, `locale()`, `allowSeeking()`, `addAllowedDomain()`, `removeAllowedDomain()` and `syncTags()`), `StatisticsFilter`, `SubtitleInput` (with `fromFile()`), `AiTaskOptions`.
- `Hihaho\Sdk\Input\Interactions`: `InteractionInput` and one class per interaction type, plus the value objects `Action`, `AdaptiveAction`, `AdaptiveCompareCondition`, `AdaptiveCompareTarget`, `AdaptiveCondition`, `AdaptiveTieOutcome`, `Border`, `Box`, `Chapter`, `GlossaryTerm`, `MenuOptions`, `Padding`, `Popup`, `SummarySection`, `Tooltip`.
- `Hihaho\Sdk\Input\Questions`: `QuestionInput` and one class per question type, plus `Answer` (with `correct()` and `wrong()`), `AnswerAction`, `EvaluationRubric`, `FeedbackTitle`, `Markup`, `MarkupButton`, `QuestionActions`, `QuestionSettings`.

The constructor parameters, the static factories and `toArray()` are stable. New constructor parameters are optional and appended last, so a positional call keeps working.

`MarkupButton` throws `InvalidArgumentException` for an enabled button whose action only continues playing or repeats the question, because the API saves such a button switched off.

`InteractionInput` and `QuestionInput` are abstract only to share code. They are sealed by intent: do not subclass them, because the API accepts only the types that the SDK ships.

## Read models

Every class in `Hihaho\Sdk\Data` is a read model: `final readonly`, with typed public properties and a static `fromArray(array $data)`. Each one has `raw(): array`, which returns the response as the API sent it. A server field that the SDK does not type yet is reachable through `raw()`. `Upload` also has `isComplete(): bool` and `isFailed(): bool`.

The constructors of the read models are internal and can change in any release. To build a read model for a test fake, call `fromArray()` with the API's response shape.

## Enums

Every enum in `Hihaho\Sdk\Enums` is stable. Enums are open on read: a read model keeps the raw value next to a nullable enum property, so a value that the SDK does not know gives `null` and never throws. Input enums hold only the values the API accepts. For example, `VideoUpdate::status()` takes `SettableVideoStatus`, not `VideoStatus`.

## Exceptions

All in `Hihaho\Sdk\Exceptions`:

- `HihahoException` (abstract, extends `RuntimeException`)
  - `HttpException` (abstract): `status(): int`, `body(): array` (the decoded JSON body, empty when the body is not JSON) and `headers(): array<string, list<string>>`. The constructor is `new XException(int $status, array $body = [], array $headers = [], ?string $message = null)`; use it to throw an exception from a test fake.
    - `AuthenticationException` (401, or a redirect)
    - `AuthorizationException` (403)
    - `NotFoundException` (404)
    - `ValidationException` (422): `errors(): array<string, list<string>>`
    - `AiTaskRefusedException` (400 from the AI task endpoint): `tasks(): array`
    - `PreconditionFailedException` (412)
    - `RateLimitedException` (429 after the retries, or with a `Retry-After` above 60 seconds)
    - `ServerException` (5xx)
    - `UnexpectedStatusException` (any other unsuccessful status)
    - `InvalidResponseException` (a successful status with a body that is not JSON, or without the `data` or `ids` it needs)
  - `TransportException`: no response (connection failure or timeout)
  - `UploadException`: `$uploadId`, `$ticket` (`?UploadTicket`) and `canResume(): bool`. `ticket` is set for every failure after the SDK registered the upload, so a caller can `send()` and `finish()` again. It is `null` for a failure in `wait()`. When `canResume()` is true, `Uploads::resume()` continues the upload.

No exception holds the HTTP request or the API token: an `HttpException` keeps only the status, body and headers, and a `TransportException` keeps only the message. An `UploadException` holds the ticket, and the ticket holds a signed upload URL. Do not dump, serialize or log exception objects to an untrusted sink; log the message instead.

## Laravel

- `Hihaho\Sdk\HihahoServiceProvider` binds `Hihaho` as a singleton.
- `Hihaho\Sdk\Facades\Hihaho` is the facade. `Hihaho::fake(array|Closure|null $responses = null)` fakes only the hihaho API. A pattern without a host, such as `v2/video/*`, is relative to the configured base URL; a pattern that starts with a scheme or a host is used as is. A Closure, or no argument, fakes every call to the base URL. Other HTTP calls of the app are not faked, and `Http::assertSent()` works as usual. A batch create of interactions or questions that no pattern matches answers 201 with one id per item. The assertions `assertCreatedInteractions(int $videoId, ?Closure $callback = null)`, `assertCreatedQuestions(…)`, `assertCreatedSubtitles(…)` and `assertUpdatedVideo(int $videoId, ?Closure $callback = null)` pass the sent items or fields, in the API's JSON shape, to the callback.
- Config keys in `config/hihaho.php`: `token`, `base_url`, `timeout`, `retries.max`, `retries.rate_limited`, `retries.server_errors_on_get`.
- Environment variables: `HIHAHO_API_TOKEN`, `HIHAHO_API_URL`, `HIHAHO_API_TIMEOUT`, `HIHAHO_API_MAX_RETRIES`. An empty `HIHAHO_API_URL` uses `Hihaho::DEFAULT_BASE_URL`. Resolving the client with an empty `HIHAHO_API_TOKEN` throws `InvalidArgumentException`.

## Code samples

`resources/code-samples.json` ships with the package. It holds `install` (the Composer command), `setup` (how to create a client) and `samples`: one PHP sample per API route, keyed by the route name, that uses a client in `$hihaho`. The keys and the shape are stable; the sample text can change in any release. A test runs every sample against a faked API.

## Internal

Not part of the public API, and can change in any release: `Http\Connector`, `Http\ErrorMapper`, `Support\*` (including `RouteMap`), `Pagination\PaginationStrategy` and its implementations (`PagePaginator`, `BareList`), `Upload\FileSender`, `Upload\ResumeStep`, `UploadException::resumeFrom()`, `Input\Fields`, `Resources\Resource` (the base class of the resources), `HihahoServiceProvider::baseUrl()`, the static named constructors of the exceptions (for example `UploadException::sendFailed()` and `InvalidResponseException::withoutData()`), the constructor of `UploadException`, and every constructor that takes one of these types.
