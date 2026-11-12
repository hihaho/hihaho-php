# hihaho PHP SDK

Typed PHP client for the [hihaho](https://www.hihaho.com) v2 API, with Laravel config support.

## Requirements

- PHP 8.4 or later.
- Laravel 12 or 13 is optional. The SDK works in plain PHP, but it uses `illuminate/http` (the Laravel HTTP client) and installs it.

## Installation

```bash
composer require hihaho/hihaho-php
```

## Authentication

The SDK sends any bearer token you give it: a personal access token, which you create in hihaho under **Profile, API tokens**, or an OAuth access token (see the [API documentation](https://api-docs.hihaho.com)). The SDK does not refresh tokens. When a token expires, create a new client with a new token.

Plain PHP:

```php
use Hihaho\Sdk\Hihaho;

$hihaho = new Hihaho(token: 'your-token');
```

An empty token throws `InvalidArgumentException`.

Laravel: set `HIHAHO_API_TOKEN` in your environment, then resolve `Hihaho\Sdk\Hihaho` from the container or use the facade. Publish the config with `php artisan vendor:publish --tag=hihaho-config` to change the base URL, timeout or retries. An empty `HIHAHO_API_URL` uses the default base URL.

```php
use Hihaho\Sdk\Facades\Hihaho;

$video = Hihaho::video(123)->get();
```

The examples below use a client in `$hihaho`.

## Videos

`videos()` lists videos and acts on several at once. `video($id)` is one video: its details, and its interactions, questions, subtitles, thumbnails, AI tasks, sessions and statistics. It sends no request until you call a method.

```php
use Hihaho\Sdk\Enums\Locale;
use Hihaho\Sdk\Enums\SettableVideoStatus;
use Hihaho\Sdk\Enums\VideoStatus;
use Hihaho\Sdk\Input\VideoUpdate;

$video = $hihaho->video(123)->get();
echo $video->playerUrl;

foreach ($hihaho->videos()->list(search: 'safety', status: VideoStatus::Published) as $summary) {
    echo $summary->displayName;
}

$hihaho->video(123)->update(VideoUpdate::make()
    ->displayName('Safety basics')
    ->status(SettableVideoStatus::Unlisted)
    ->locale(Locale::NlNL)
    ->syncTags(['onboarding']));
```

A video that only selected viewers may watch needs a viewer token per viewer. The token gives a personal player and embed URL:

```php
$token = $hihaho->video(123)->createViewerToken('user-42', lifetimeSeconds: 3600);
echo $token->playerUrl;
```

## Folders

```php
foreach ($hihaho->folders()->list() as $folder) {
    echo $folder->name;
}

$child = $hihaho->folders()->createChild(parentId: 7, name: 'Team A', externalIdentifier: 'team-a');
```

## Uploading a video

Run uploads in a queued job, not in a web request: a large file takes longer to send than a web request may last.

### In one call

`upload()` registers the upload, sends the file and tells hihaho that the file is sent. It returns the `Upload` right after that, so the video can still be processing.

```php
$upload = $hihaho->uploads()->upload(folderId: 7, file: '/path/to/video.mp4');
```

You can also pass an open resource or a PSR-7 stream. It must be seekable and have a known size. Without `name`, the SDK uses the file name when the resource reads a local file. The SDK does not close it; you close it.

```php
use Illuminate\Support\Facades\Storage;

$resource = Storage::disk('local')->readStream('videos/intro.mp4');

try {
    $upload = $hihaho->uploads()->upload(folderId: 7, file: $resource);
} finally {
    fclose($resource);
}
```

A stream from a cloud disk, such as S3, cannot seek. Copy the file to a local path first.

In a queued job you can wait until the video platform has processed the video. `wait()` polls until the upload is ready or fails, or until the timeout passes. It takes a `DateInterval`, such as a `CarbonInterval`, or a number of seconds.

```php
use Carbon\CarbonInterval;

$upload = $hihaho->uploads()->wait($upload, timeout: CarbonInterval::minutes(30), interval: 10);
```

### Step by step

Use the steps to keep the upload id between jobs, or to control each step.

1. `register()` creates the upload in a folder. Send the real byte size, because some video platforms require it.

```php
$path = '/path/to/video.mp4';
$size = filesize($path);

if ($size === false) {
    throw new RuntimeException("Cannot read {$path}.");
}

$ticket = $hihaho->uploads()->register(folderId: 7, name: 'video.mp4', size: $size);
```

2. The returned `UploadTicket` holds the upload `id`, the `url` to send the file to, the `uploadMethod` (an `UploadMethod`, or `null` when this SDK version does not know the method), and the `video` that hihaho created for the upload (`id` and `uuid`).

```php
echo "Upload {$ticket->id} sends to {$ticket->url}.";
echo "The video id is {$ticket->video?->id}.";
```

3. `send()` sends the file to the upload URL. The SDK picks the transfer for the upload method. The request to the upload host does not carry your API token.

```php
$hihaho->uploads()->send($ticket, '/path/to/video.mp4');
```

4. `finish()` tells hihaho that the file is sent. It is safe to call it twice.

```php
$hihaho->uploads()->finish($ticket->id);
```

5. `wait()` polls until the video platform has processed the video. `get()` reads the status once.

```php
$upload = $hihaho->uploads()->wait($ticket->id, timeout: 1800);

$upload = $hihaho->uploads()->get($ticket->id);
echo $upload->isComplete() ? 'ready' : $upload->statusDescription;
```

6. The `Upload` read model lists its videos in `videos`.

```php
$upload = $hihaho->uploads()->get($ticket->id);

foreach ($upload->videos as $video) {
    echo $video->id;
}
```

`createVideo()` returns the video of a finished upload, and is safe to repeat. `deleteFailed()` removes the failed uploads of a folder.

```php
$video = $hihaho->uploads()->createVideo($ticket->id);

$hihaho->uploads()->deleteFailed(folderId: 7);
```

### Resume an upload

After registering, every failure in `upload()` throws `UploadException`. When `canResume()` is true, `resume()` continues from the step that failed: it sends the file again if the send failed, then finishes and reads the upload. A resumable (tus) upload continues from the bytes that the upload host already stored; the other upload methods send the whole file again.

```php
use Hihaho\Sdk\Exceptions\UploadException;

$path = '/path/to/video.mp4';

try {
    $upload = $hihaho->uploads()->upload(folderId: 7, file: $path);
} catch (UploadException $exception) {
    if (! $exception->canResume()) {
        throw $exception; // The upload failed on the server. Register a new upload.
    }

    $upload = $hihaho->uploads()->resume($exception, $path);
}

$upload = $hihaho->uploads()->wait($upload);
```

`resume()` can throw a new `UploadException`. Resume that one the same way.

When you use the steps yourself, this is what each step throws, and what to do:

| Step | Exception | What to do |
|---|---|---|
| `register()` | an `HttpException` subclass, for example `ValidationException` | Fix the input and register again. No upload exists yet. |
| `send()` | `UploadException` with the ticket | Call `send()` again with the ticket. |
| `finish()` | `UnexpectedStatusException` with status 409 | Another call is still finishing the upload. Wait, then call `finish()` again. |
| `finish()` | `ValidationException` (422) | The upload failed on the server. Register a new upload. |
| `finish()` | any other `HihahoException` | Call `finish()` again. |
| `wait()` | `UploadException` that says the upload was not processed in time | Call `wait()` again. |
| `wait()` | `UploadException` that says the upload failed on the video platform | Register a new upload. |
| `wait()`, `get()` | an `HttpException` subclass or `TransportException` | Call it again. |

Inside `upload()`, every failure after registering is an `UploadException`. Its message names the step, and `getPrevious()` holds the original exception.

## Subtitles

```php
use Hihaho\Sdk\Enums\SubtitleType;
use Hihaho\Sdk\Input\SubtitleInput;

$hihaho->video(123)->subtitles()->createMany([
    SubtitleInput::fromFile('/path/to/en.vtt', title: 'English', language: 'en', isDefault: true),
    new SubtitleInput(title: 'Nederlands', language: 'nl', content: $vtt, type: SubtitleType::Vtt),
]);
```

`fromFile()` takes the type from a `.vtt` or `.srt` extension. For another extension, pass `type`.

## Thumbnails

```php
$hihaho->video(123)->thumbnails()->upload('/path/to/poster.jpg');

foreach ($hihaho->video(123)->thumbnails()->list() as $thumbnail) {
    echo $thumbnail->deliveryUrl;
}
```

## Interactions and questions

```php
use Hihaho\Sdk\Input\Interactions\Action;
use Hihaho\Sdk\Input\Interactions\Box;
use Hihaho\Sdk\Input\Interactions\TextInteraction;
use Hihaho\Sdk\Input\Questions\Answer;
use Hihaho\Sdk\Input\Questions\MultipleChoiceQuestion;

$interactionIds = $hihaho->video(123)->interactions()->createMany([
    new TextInteraction(startTime: 5, endTime: 10, text: 'Watch this', box: new Box(top: '10%', left: '10%', width: '30%', height: '10%'), action: Action::jump(42)),
]);

$questionIds = $hihaho->video(123)->questions()->createMany([
    new MultipleChoiceQuestion(activeAt: 30, text: 'Which one?', answers: [Answer::correct('A'), Answer::wrong('B')]),
]);
```

Each interaction and question type has its own class with only the fields the API keeps for that type. A batch store saves every item or none, and returns the new ids in the order you sent the items.

To jump to an item in the same batch, give the target a `ref` and point the action at it. An interaction can jump to an interaction or a chapter item by ref; a question can jump to a question by ref. Refs do not cross between the two batches. The API answers 422 when a ref is used twice in one batch, or when no item in the batch has the target ref.

```php
use Hihaho\Sdk\Input\Interactions\Chapter;
use Hihaho\Sdk\Input\Interactions\ChapterInteraction;

$hihaho->video(123)->interactions()->createMany([
    new ChapterInteraction(startTime: 0, endTime: 60, chapters: [new Chapter('Summary', 50, 60, ref: 'summary')]),
    new TextInteraction(startTime: 5, endTime: 10, text: 'Skip to the summary', action: Action::jumpToChapterRef('summary')),
]);
```

Every interaction type also takes an `AdaptiveCondition` or an `AdaptiveCompareCondition`, which act on the viewer's score per adaptive subject. Make the subjects in the studio first: the API answers 422 when adaptive testing is off for the video, or when the video has no subject at the given position.

## Statistics and sessions

```php
use Hihaho\Sdk\Input\StatisticsFilter;

$filter = new StatisticsFilter(from: new DateTimeImmutable('2026-10-01'), until: new DateTimeImmutable('2026-10-31'));

foreach ($hihaho->video(123)->statistics()->views($filter) as $views) {
    echo $views->total;
}

foreach ($hihaho->video(123)->sessions()->list($filter) as $session) {
    echo $session->scaledResult;
}
```

## AI tasks

`AiTaskType::startable()` lists the task types that the API can start. A task runs in the background; read `aiTasks()->list()` again to follow its status. The list is newest first, 10 tasks per page.

```php
use Hihaho\Sdk\Enums\AiTaskStatus;
use Hihaho\Sdk\Enums\AiTaskType;

$hihaho->video(123)->aiTasks()->start(AiTaskType::GenerateChapters);

foreach ($hihaho->video(123)->aiTasks()->list() as $task) {
    if ($task->taskType === AiTaskType::GenerateChapters && $task->status === AiTaskStatus::Succeeded) {
        echo 'Chapters are ready.';
    }
}
```

## Lists and pagination

`list()` returns a `ListRequest`. Iterate it with `foreach`, call `all()` for a `LazyCollection`, or read one page at a time. `nextPage()` returns `null` after the last page.

```php
use Hihaho\Sdk\Data\VideoSummary;

$page = $hihaho->videos()->list()->page();

while ($page !== null) {
    foreach ($page->items as $video) {
        echo $video->displayName;
    }

    $page = $page->nextPage();
}

$names = $hihaho->videos()->list()->all()->map(fn (VideoSummary $video): string => $video->displayName);
```

`all()` is lazy and makes one request per page. It skips rows that were already returned on an earlier page. `foreach` over a `ListRequest` uses `all()`.

## Errors

Every API, transport and upload failure throws a subclass of `Hihaho\Sdk\Exceptions\HihahoException`. An `HttpException` gives the `status()`, the decoded `body()` and the `headers()` of the response. Invalid input, such as a batch over the maximum, throws `InvalidArgumentException` before anything is sent.

```php
use Hihaho\Sdk\Exceptions\HihahoException;
use Hihaho\Sdk\Exceptions\NotFoundException;
use Hihaho\Sdk\Exceptions\UploadException;
use Hihaho\Sdk\Exceptions\ValidationException;
use Hihaho\Sdk\Input\VideoUpdate;

try {
    $hihaho->video(123)->update(VideoUpdate::make()->displayName('Safety basics'));
    $hihaho->uploads()->upload(folderId: 7, file: '/path/to/video.mp4');
} catch (ValidationException $exception) {
    foreach ($exception->errors() as $field => $messages) {
        echo $field . ': ' . implode(' ', $messages);
    }
} catch (NotFoundException) {
    echo 'The video does not exist.';
} catch (UploadException $exception) {
    echo "Upload {$exception->uploadId} failed: {$exception->getMessage()}";
    // $exception->ticket resumes the upload; see "Resume an upload".
} catch (HihahoException $exception) {
    echo $exception->getMessage();
}
```

No exception holds the request or your token. An `UploadException` holds the upload ticket, and the ticket holds a signed upload URL. Do not dump, serialize or log exception objects to an untrusted sink; log the message instead.

## Retries

By default the SDK retries a request up to 3 times:

- A 429 is retried after its `Retry-After`, up to 60 seconds. A longer `Retry-After` throws `RateLimitedException` at once.
- A GET is also retried after a 5xx or a connection failure, with a backoff of 1, 2 and 4 seconds.

Change this with `new RetryPolicy(...)`, or turn it off with `RetryPolicy::disabled()`. In Laravel, use the `retries` config keys.

## Testing your integration

In a Laravel app, `Hihaho::fake()` fakes only the hihaho API. A pattern without a host is relative to the configured base URL. Other HTTP calls of your app are not faked, and `Http::assertSent()` works as usual. To test an upload, also fake the upload host with a pattern that starts with that host.

When no token is configured, `Hihaho::fake()` sets a placeholder token, so tests need none. A batch create of interactions or questions answers with one id per item, so `createMany()` works without setup.

The facade asserts what your code sent, in the API's JSON shape:

```php
use Hihaho\Sdk\Facades\Hihaho;

Hihaho::fake();

// ... run the code under test ...

Hihaho::assertCreatedInteractions(123, fn (array $items): bool => $items[0]['text'] === 'Watch this');
Hihaho::assertCreatedQuestions(123);
Hihaho::assertCreatedSubtitles(123, fn (array $items): bool => $items[0]['language'] === 'en');
Hihaho::assertUpdatedVideo(123, fn (array $fields): bool => $fields['display_name'] === 'Safety basics');
```

For a call that reads data, pass the response:

```php
use Illuminate\Support\Facades\Http;

Hihaho::fake([
    'v2/video/123' => Http::response(['data' => ['id' => 123, 'display_name' => 'Safety basics']]),
]);
```

In plain PHP, pass a faked HTTP factory. `RetryPolicy::disabled()` keeps a faked error from being retried.

```php
use Hihaho\Sdk\Hihaho;
use Hihaho\Sdk\Http\RetryPolicy;
use Illuminate\Http\Client\Factory;

$http = new Factory();
$http->fake(['*' => Factory::response(['data' => ['id' => 123]])]);

$hihaho = new Hihaho(token: 't', http: $http, retryPolicy: RetryPolicy::disabled());
```

To build a read model for a test, call `fromArray()` with the response shape of the API:

```php
use Hihaho\Sdk\Data\Folder;

$folder = Folder::fromArray(['id' => 7, 'name' => 'Training', 'created_at' => '2026-10-15 14:09:51', 'parent_id' => null, 'external_identifier' => 'team-1']);
```

## Versioning

The SDK follows semantic versioning. Before 1.0, a minor version can break the public API; a patch version cannot. Require `^0.1` to get only the patch releases of 0.1. [PUBLIC_API.md](PUBLIC_API.md) lists the stable public surface. Everything else is internal.

## Known API limitations

See [GAPS.md](GAPS.md).

## Development

```bash
composer test
```

## License

Proprietary. Copyright hihaho. See [LICENSE](LICENSE).
