<?php declare(strict_types=1);

namespace Hihaho\Sdk\Support;

/**
 * Every public v2 route, its HTTP method and URI, and the SDK method that calls it. A route
 * with no SDK method is in `EXCLUDED`, with its reason. `tests/Unit/RouteMapTest.php` keeps
 * this in step with the API's route list.
 */
final class RouteMap
{
    /**
     * Route name => HTTP method, URI, and the chain of calls from `Hihaho` to the SDK method.
     *
     * @var array<string, array{0: string, 1: string, 2: non-empty-list<string>}>
     */
    public const array ROUTES = [
        'api.v2.video-container.index' => ['GET', 'v2/video-container', ['folders', 'list']],
        'api.v2.video-container.create-child' => ['POST', 'v2/video-container/{container}/create', ['folders', 'createChild']],
        'api.v2.video-container.upload-video.index' => ['GET', 'v2/video-container/{container}/upload-video', ['uploads', 'list']],
        'api.v2.video-container.upload-video.store' => ['POST', 'v2/video-container/{container}/upload-video', ['uploads', 'register']],
        'api.v2.video-container.upload-video.destroy.failed' => ['DELETE', 'v2/video-container/{container}/upload-video/failed', ['uploads', 'deleteFailed']],
        'api.v2.video-container.video-from-platform' => ['POST', 'v2/video-container/{container}/video-from-platform', ['videos', 'createFromPlatform']],
        'api.v2.upload-video.video.show' => ['GET', 'v2/upload-video/{upload_video}', ['uploads', 'get']],
        'api.v2.upload-video.video.store' => ['POST', 'v2/upload-video/{upload_video}/video', ['uploads', 'createVideo']],
        'api.v2.upload-video.finished' => ['POST', 'v2/upload-video/{upload_video}/finished', ['uploads', 'finish']],
        'api.v2.video.index' => ['GET', 'v2/video', ['videos', 'list']],
        'api.v2.video.show' => ['GET', 'v2/video/{video_id}', ['video', 'get']],
        'api.v2.video.update' => ['PUT', 'v2/video/{video_id}', ['video', 'update']],
        'api.v2.video.export' => ['GET', 'v2/video/{video_id}/export', ['video', 'export']],
        'api.v2.video.destroy.batch' => ['DELETE', 'v2/video/batch', ['videos', 'deleteMany']],
        'api.v2.video.move.batch' => ['PUT', 'v2/video/batch/move/{container}', ['videos', 'moveMany']],
        'api.v2.video.swap-uuid' => ['POST', 'v2/video/swap/from/{videoFrom}/to/{videoTo}', ['videos', 'swap']],
        'api.v2.video.allowed-viewer-token.store' => ['POST', 'v2/video/{video_id}/allowed_viewer_token', ['video', 'createViewerToken']],
        'api.v2.video.thumbnail.update' => ['POST', 'v2/video/{video_id}/thumbnail', ['video', 'thumbnails', 'upload']],
        'api.v2.video.thumbnail.index' => ['GET', 'v2/video/{video_id}/thumbnail', ['video', 'thumbnails', 'list']],
        'api.v2.video.thumbnail.activate' => ['PUT', 'v2/video/{video_id}/thumbnail/{thumbnail_id}/activate', ['video', 'thumbnails', 'activate']],
        'api.v2.video.thumbnail.destroy' => ['DELETE', 'v2/video/{video_id}/thumbnail/{thumbnail_id}', ['video', 'thumbnails', 'delete']],
        'api.v2.video.ai-task.index' => ['GET', 'v2/video/{video_id}/ai-task', ['video', 'aiTasks', 'list']],
        'api.v2.video.ai-task.create' => ['POST', 'v2/video/{video_id}/ai-task', ['video', 'aiTasks', 'start']],
        'api.v2.video.subtitles' => ['GET', 'v2/video/{video_id}/subtitles', ['video', 'subtitles', 'list']],
        'api.v2.video.subtitle.store.batch' => ['POST', 'v2/video/{video_id}/subtitle/batch', ['video', 'subtitles', 'createMany']],
        'api.v2.video.statistics.aggregated' => ['GET', 'v2/video/{video_id}/aggregated-statistics', ['video', 'statistics', 'aggregated']],
        'api.v2.video.session.index' => ['GET', 'v2/video/{video_id}/session', ['video', 'sessions', 'list']],
        'api.v2.video.session.show' => ['GET', 'v2/video/{video_id}/session/{video_session}', ['video', 'sessions', 'get']],
        'api.v2.video.stats.views' => ['GET', 'v2/video/{video_id}/stats/views', ['video', 'statistics', 'views']],
        'api.v2.video.stats.views-by-user-agent' => ['GET', 'v2/video/{video_id}/stats/views-by-user-agent', ['video', 'statistics', 'viewsByUserAgent']],
        'api.v2.video.stats.interactions' => ['GET', 'v2/video/{video_id}/stats/interactions', ['video', 'statistics', 'interactions']],
        'api.v2.video.stats.questions' => ['GET', 'v2/video/{video_id}/stats/questions', ['video', 'statistics', 'questions']],
        'api.v2.video.stats.metadata.list' => ['GET', 'v2/video/{video_id}/stats/metadata', ['video', 'statistics', 'metadata']],
        'api.v2.video.stats.metadata.show' => ['GET', 'v2/video/{video_id}/stats/metadata/{video_session}', ['video', 'statistics', 'sessionMetadata']],
        'api.v2.video.interaction.index' => ['GET', 'v2/video/{video_id}/interaction', ['video', 'interactions', 'list']],
        'api.v2.video.interaction.show' => ['GET', 'v2/video/{video_id}/interaction/{interaction_id}', ['video', 'interactions', 'get']],
        'api.v2.video.interaction.store.batch' => ['POST', 'v2/video/{video_id}/interaction/batch', ['video', 'interactions', 'createMany']],
        'api.v2.video.interaction.destroy.batch' => ['DELETE', 'v2/video/{video_id}/interaction/batch', ['video', 'interactions', 'deleteMany']],
        'api.v2.video.interaction.destroy' => ['DELETE', 'v2/video/{video_id}/interaction/{interaction_id}', ['video', 'interactions', 'delete']],
        'api.v2.video.question.index' => ['GET', 'v2/video/{video_id}/question', ['video', 'questions', 'list']],
        'api.v2.video.question.show' => ['GET', 'v2/video/{video_id}/question/{question_id}', ['video', 'questions', 'get']],
        'api.v2.video.question.store.batch' => ['POST', 'v2/video/{video_id}/question/batch', ['video', 'questions', 'createMany']],
        'api.v2.video.question.destroy.batch' => ['DELETE', 'v2/video/{video_id}/question/batch', ['video', 'questions', 'deleteMany']],
        'api.v2.video.question.destroy' => ['DELETE', 'v2/video/{video_id}/question/{question_id}', ['video', 'questions', 'delete']],
    ];

    /**
     * Route name => why the SDK does not call it.
     *
     * @var array<string, string>
     */
    public const array EXCLUDED = [];
}
