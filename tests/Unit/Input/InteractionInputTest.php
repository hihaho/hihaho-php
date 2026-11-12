<?php declare(strict_types=1);

namespace Hihaho\Sdk\Tests\Unit\Input;

use Closure;
use Hihaho\Sdk\Enums\AdaptiveCompareMode;
use Hihaho\Sdk\Enums\AdaptiveComparison;
use Hihaho\Sdk\Enums\AlignItems;
use Hihaho\Sdk\Enums\BorderStyle;
use Hihaho\Sdk\Enums\ContinueButtonDisplayMode;
use Hihaho\Sdk\Enums\DisplayLocation;
use Hihaho\Sdk\Enums\FrameContinuePlaying;
use Hihaho\Sdk\Enums\HotspotBlinkingSpeed;
use Hihaho\Sdk\Enums\Locale;
use Hihaho\Sdk\Enums\MenuButtonPosition;
use Hihaho\Sdk\Enums\MenuPosition;
use Hihaho\Sdk\Enums\PauseContent;
use Hihaho\Sdk\Enums\PopupButtonLabel;
use Hihaho\Sdk\Enums\PreviousVideoButtonMode;
use Hihaho\Sdk\Enums\ScrollDirection;
use Hihaho\Sdk\Enums\TextStroke;
use Hihaho\Sdk\Enums\TooltipPlacement;
use Hihaho\Sdk\Enums\VideoFontSize;
use Hihaho\Sdk\Input\Interactions\Action;
use Hihaho\Sdk\Input\Interactions\AdaptiveAction;
use Hihaho\Sdk\Input\Interactions\AdaptiveCompareCondition;
use Hihaho\Sdk\Input\Interactions\AdaptiveCompareTarget;
use Hihaho\Sdk\Input\Interactions\AdaptiveCondition;
use Hihaho\Sdk\Input\Interactions\AdaptiveTieOutcome;
use Hihaho\Sdk\Input\Interactions\Border;
use Hihaho\Sdk\Input\Interactions\Box;
use Hihaho\Sdk\Input\Interactions\Chapter;
use Hihaho\Sdk\Input\Interactions\ChapterInteraction;
use Hihaho\Sdk\Input\Interactions\FrameInteraction;
use Hihaho\Sdk\Input\Interactions\GlossaryInteraction;
use Hihaho\Sdk\Input\Interactions\GlossaryTerm;
use Hihaho\Sdk\Input\Interactions\HighlightInteraction;
use Hihaho\Sdk\Input\Interactions\HotspotInteraction;
use Hihaho\Sdk\Input\Interactions\ImageInteraction;
use Hihaho\Sdk\Input\Interactions\InteractionInput;
use Hihaho\Sdk\Input\Interactions\JumpToInteraction;
use Hihaho\Sdk\Input\Interactions\LinkInteraction;
use Hihaho\Sdk\Input\Interactions\LinkVideoInteraction;
use Hihaho\Sdk\Input\Interactions\MenuInteraction;
use Hihaho\Sdk\Input\Interactions\MenuOptions;
use Hihaho\Sdk\Input\Interactions\Padding;
use Hihaho\Sdk\Input\Interactions\PauseInteraction;
use Hihaho\Sdk\Input\Interactions\Popup;
use Hihaho\Sdk\Input\Interactions\ScrollInteraction;
use Hihaho\Sdk\Input\Interactions\SoundInteraction;
use Hihaho\Sdk\Input\Interactions\SummaryInteraction;
use Hihaho\Sdk\Input\Interactions\SummarySection;
use Hihaho\Sdk\Input\Interactions\TextInteraction;
use Hihaho\Sdk\Input\Interactions\Tooltip;
use Hihaho\Sdk\Input\Interactions\ZoomInteraction;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The allowed paths per type are the fields the API's interaction import accepts for that type:
 * a field outside them is dropped by the API without an error.
 */
final class InteractionInputTest extends TestCase
{
    private const array COMMON = ['type', 'title', 'start_time', 'end_time', 'should_fade_volume', ...self::SHARED];

    private const array SHARED = ['ref', 'adaptive_condition.subject_sort_order', 'adaptive_condition.comparison', 'adaptive_condition.percentage', 'adaptive_condition.fallback_action_type', 'adaptive_condition.fallback_action_value', 'adaptive_compare_condition.comparison_mode', 'adaptive_compare_condition.default_action_type', 'adaptive_compare_condition.default_action_value', 'adaptive_compare_condition.targets', 'adaptive_compare_condition.tie_outcomes'];

    private const array BOX = ['style.top', 'style.left', 'style.width', 'style.height', 'style.border_radius', 'style.border.border_width', 'style.border.border_style', 'style.border.border_color'];

    private const array PAD = ['text_stroke', 'style.padding_top', 'style.padding_bottom', 'style.align_items'];

    private const array TEXT = ['text', 'attributes.should_use_vertical_text_orientation'];

    private const array FADE = ['should_fade_in', 'should_fade_out'];

    private const array TOOLTIP = ['attributes.tooltip_text', 'attributes.tooltip_placement'];

    private const array POPUP = ['should_show_as_popup', 'style.popup_button_left', 'style.popup_button_top', 'attributes.popup_button_label_type', 'attributes.popup_button_label_text'];

    private const array ACTION = ['action.type', 'action.time', 'action.interaction_id', 'action.question_id', 'action.chapter_id', 'action.jump_at_position', 'action.activation_style.background_color', 'action.activation_style.text_color', 'action.subtitle_route_key', 'action.target_ref'];

    private const array LINK = ['action.link', 'action.should_open_in_same_window'];

    private const array OPTIONS = ['attributes.options.menu_button_location', 'attributes.options.menu_button_name', 'attributes.options.menu_button_position', 'attributes.options.should_give_possibility_to_close', 'attributes.options.should_show_menu_button'];

    /**
     * @param  list<string>  $allowed
     * @param  list<string>  $expectedSent
     */
    #[Test]
    #[DataProvider('fullyFilledProvider')]
    public function every_field_is_one_the_api_keeps_for_the_type(InteractionInput $interaction, array $allowed, array $expectedSent): void
    {
        $sent = self::leafPaths($interaction->toArray());

        $this->assertSame([], array_values(array_diff($sent, [...self::COMMON, ...$allowed])), 'Sent paths the API drops for this type.');
        $this->assertSame([], array_values(array_diff([...self::SHARED, ...$expectedSent], $sent)), 'Set fields that were not sent.');
        $this->assertSame($interaction->type()->value, $interaction->toArray()['type']);
    }

    /**
     * @return iterable<string, array{InteractionInput, list<string>, list<string>}>
     */
    public static function fullyFilledProvider(): iterable
    {
        $box = new Box('1%', '2%', '3%', '4%', new Border(1, BorderStyle::Dashed, '#fff'), 5);
        $tooltip = new Tooltip('Tip', TooltipPlacement::Top);
        $popup = new Popup(PopupButtonLabel::CustomText, 'Open', '40%', '50%');
        $padding = new Padding(1, 2, AlignItems::Center);
        $options = new MenuOptions(DisplayLocation::PlayerBar, 'Menu', MenuButtonPosition::TopLeft, true, true);
        $common = [
            'startTime' => 1.5,
            'title' => 'T',
            'shouldFadeVolume' => true,
            'ref' => 'item',
            'adaptiveCondition' => new AdaptiveCondition(1, AdaptiveComparison::AtLeast, 50, AdaptiveAction::jump(90)),
            'adaptiveCompareCondition' => new AdaptiveCompareCondition(AdaptiveCompareMode::Highest, AdaptiveAction::jumpToInteraction(4), [new AdaptiveCompareTarget(1, AdaptiveAction::jumpToEnd())], [new AdaptiveTieOutcome(AdaptiveAction::jumpToQuestion(5), [1, 2])]),
        ];

        yield 'chapter' => [new ChapterInteraction(...$common, endTime: 9, chapters: [new Chapter('One', 1, 2, 'O', 1, false)], position: MenuPosition::Left, startCollapsed: true, collapseAfterChapterClick: true, pauseWhenShown: true, useMenuLayout: true, desktopFontSize: VideoFontSize::Pt14, mobileFontSize: VideoFontSize::Pt16, trackVisits: true, menuOptions: $options),
            ['position', 'should_start_collapsed', 'should_collapse_after_menu_item_click', 'should_pause_when_shown', 'should_use_menu_layout', 'chapters', 'style.desktop_font_size', 'style.mobile_font_size', 'attributes.visit_tracking.enabled', ...self::OPTIONS],
            [...self::OPTIONS, 'chapters', 'position', 'should_start_collapsed', 'should_collapse_after_menu_item_click', 'should_pause_when_shown', 'should_use_menu_layout', 'style.desktop_font_size', 'style.mobile_font_size', 'attributes.visit_tracking.enabled']];
        yield 'menu' => [new MenuInteraction(...$common, position: MenuPosition::Center, pauseWhenShown: true, notTimeBound: true, menuOptions: $options, showChapterNumbers: true),
            ['position', 'should_pause_when_shown', 'should_not_use_time', 'should_show_chapter_numbers', ...self::OPTIONS],
            [...self::OPTIONS, 'position', 'should_pause_when_shown', 'should_not_use_time', 'should_show_chapter_numbers']];
        yield 'frame' => [new FrameInteraction(...$common, box: $box, backgroundColor: '#000', fadeIn: true, fadeOut: true, popup: $popup, action: Action::link('https://example.com', true), pauseVideo: true, continuePlaying: FrameContinuePlaying::ClickOutsideFrameElement, buttonPosition: MenuButtonPosition::TopRight, resumeVideoWithEvent: true, blockPlayback: true, objectFit: 'cover', stayAliveAfterHiding: true),
            [...self::BOX, 'style.background_color', ...self::FADE, ...self::POPUP, ...self::ACTION, ...self::LINK, 'action.should_pause_video', 'action.frame_continue_playing_option', 'action.frame_button_position', 'action.should_resume_video_with_event', 'action.should_block_playback', 'style.object_fit', 'attributes.should_stay_alive_after_hiding'],
            [...self::BOX, ...self::FADE, ...self::POPUP, ...self::LINK, 'style.background_color', 'style.object_fit', 'attributes.should_stay_alive_after_hiding', 'action.type', 'action.should_pause_video', 'action.frame_continue_playing_option', 'action.frame_button_position', 'action.should_resume_video_with_event', 'action.should_block_playback']];
        yield 'highlight' => [new HighlightInteraction(...$common, endTime: 3, box: $box, backgroundColor: '#000', fadeIn: true, fadeOut: true, tooltip: $tooltip, action: Action::jump(2)),
            [...self::BOX, 'style.background_color', ...self::FADE, ...self::TOOLTIP, ...self::ACTION, ...self::LINK],
            [...self::BOX, ...self::FADE, ...self::TOOLTIP, 'style.background_color', 'action.type', 'action.time']];
        yield 'hotspot' => [new HotspotInteraction(...$common, endTime: 3, showIndicator: true, box: $box, backgroundColor: '#000', text: 'Hi', verticalText: true, action: Action::showScoreScreen(), indicatorColor: '#f00', textLabelColor: '#0f0', blinkingSpeed: HotspotBlinkingSpeed::VerySlow, textRenderScale: 2, tooltipPlacement: TooltipPlacement::Auto),
            [...self::BOX, 'style.background_color', ...self::TEXT, ...self::ACTION, ...self::LINK, 'attributes.should_show_indicator', 'attributes.indicator_color', 'attributes.text_label_color', 'attributes.blinking_speed', 'attributes.text_render_scale', 'attributes.tooltip_placement'],
            [...self::BOX, ...self::TEXT, 'attributes.should_show_indicator', 'attributes.indicator_color', 'attributes.text_label_color', 'attributes.blinking_speed', 'attributes.text_render_scale', 'attributes.tooltip_placement', 'style.background_color', 'action.type']];
        yield 'image' => [new ImageInteraction(...$common, endTime: 3, imageUrl: '/img/default_image_interaction.png', box: $box, backgroundColor: '#000', fadeIn: true, fadeOut: true, popup: $popup, tooltip: $tooltip, action: Action::jumpToInteraction(4), objectFit: 'cover', opacity: 0.5, clickThrough: true, playGifOnMouseover: true),
            ['image_url', ...self::BOX, 'style.background_color', ...self::FADE, ...self::POPUP, ...self::TOOLTIP, ...self::ACTION, ...self::LINK, 'style.object_fit', 'style.opacity', 'attributes.should_click_through', 'should_play_gif_on_mouseover'],
            [...self::BOX, ...self::FADE, ...self::TOOLTIP, ...self::POPUP, 'image_url', 'style.background_color', 'style.object_fit', 'style.opacity', 'attributes.should_click_through', 'should_play_gif_on_mouseover', 'action.type', 'action.interaction_id']];
        yield 'jump to' => [new JumpToInteraction(...$common, action: Action::jump(30)), self::ACTION,
            ['action.type', 'action.time']];
        yield 'link' => [new LinkInteraction(...$common, endTime: 3, box: $box, tooltip: $tooltip, action: Action::link('https://example.com')), [...self::BOX, ...self::TOOLTIP, ...self::ACTION, ...self::LINK],
            [...self::BOX, ...self::TOOLTIP, 'action.type', 'action.link']];
        yield 'link video' => [new LinkVideoInteraction(...$common, endTime: 3, box: $box, backgroundColor: '#000', text: 'Go', verticalText: false, textStroke: TextStroke::Black, padding: $padding, fadeIn: true, fadeOut: true, tooltip: $tooltip, action: Action::linkVideo('uuid'), targetStartTime: 4, previousVideoButton: PreviousVideoButtonMode::Everywhere, previousVideoButtonText: 'Back'),
            [...self::BOX, 'style.background_color', ...self::TEXT, ...self::PAD, ...self::FADE, ...self::TOOLTIP, ...self::ACTION, ...self::LINK, 'action.link_video_target_custom_start_time', 'action.previous_video_button_mode', 'action.previous_video_button_text'],
            [...self::BOX, ...self::PAD, ...self::TEXT, ...self::FADE, ...self::TOOLTIP, 'style.background_color', 'action.type', 'action.link', 'action.link_video_target_custom_start_time', 'action.previous_video_button_mode', 'action.previous_video_button_text']];
        yield 'pause' => [new PauseInteraction(...$common, box: $box, backgroundColor: '#000', text: 'Wait', verticalText: true, textStroke: TextStroke::White, padding: $padding, fadeIn: true, fadeOut: true, action: Action::jumpToEnd(), content: PauseContent::Image, imageUrl: '/img/default_image_interaction.png', soundUrl: '/sound/no_audio_sound.mp3', enableSound: true, continueButton: ContinueButtonDisplayMode::TextOnly, continueButtonLeft: '1%', continueButtonTop: '2%', objectFit: 'cover', opacity: 1, clickThrough: true, blockPlayback: true, playGifOnMouseover: true),
            [...self::BOX, 'style.background_color', ...self::TEXT, ...self::PAD, ...self::FADE, ...self::ACTION, 'content_mode', 'image_url', 'sound_url', 'should_enable_sound', 'continue_button_display_mode', 'style.continue_button_left', 'style.continue_button_top', 'style.object_fit', 'style.opacity', 'attributes.should_click_through', 'should_play_gif_on_mouseover', 'action.should_block_playback'],
            [...self::BOX, ...self::PAD, ...self::TEXT, ...self::FADE, 'style.background_color', 'style.continue_button_left', 'style.continue_button_top', 'style.object_fit', 'style.opacity', 'attributes.should_click_through', 'should_play_gif_on_mouseover', 'content_mode', 'image_url', 'sound_url', 'should_enable_sound', 'continue_button_display_mode', 'action.type', 'action.should_block_playback']];
        yield 'scroll' => [new ScrollInteraction(...$common, endTime: 3, box: $box, backgroundColor: '#000', text: 'News', verticalText: false, textStroke: TextStroke::None, padding: $padding, fadeIn: true, fadeOut: true, tooltip: $tooltip, action: Action::none(), flyIn: true, scrollDuration: 10, scrollDirection: ScrollDirection::Rtl),
            [...self::BOX, 'style.background_color', ...self::TEXT, ...self::PAD, ...self::FADE, ...self::TOOLTIP, ...self::ACTION, ...self::LINK, 'attributes.should_fly_in', 'attributes.scroll_duration', 'attributes.scroll_direction'],
            [...self::BOX, ...self::PAD, ...self::TEXT, ...self::FADE, ...self::TOOLTIP, 'style.background_color', 'attributes.should_fly_in', 'attributes.scroll_duration', 'attributes.scroll_direction', 'action.type']];
        yield 'sound' => [new SoundInteraction(...$common, endTime: 3, soundUrl: '/sound/no_audio_sound.mp3', enableSound: true), ['sound_url', 'should_enable_sound'],
            ['sound_url', 'should_enable_sound']];
        yield 'summary' => [new SummaryInteraction(...$common, sections: [new SummarySection('Body', 'Head', 1, 2, 1)], locale: Locale::NlNL), ['summary_sections', 'locale'],
            ['summary_sections', 'locale']];
        yield 'glossary' => [new GlossaryInteraction(...$common, terms: [new GlossaryTerm('Term', 'Definition', ['term'], [['start' => 1.0, 'end' => 2.0]], 1)], locale: Locale::EnUS), ['glossary_terms', 'locale'],
            ['glossary_terms', 'locale']];
        yield 'text' => [new TextInteraction(...$common, endTime: 3, box: $box, backgroundColor: '#000', text: 'Hi', verticalText: true, textStroke: TextStroke::Black, padding: $padding, fadeIn: true, fadeOut: true, popup: $popup, tooltip: $tooltip, action: Action::delayedJump(5, 2, '#000', '#fff')),
            [...self::BOX, 'style.background_color', ...self::TEXT, ...self::PAD, ...self::FADE, ...self::POPUP, ...self::TOOLTIP, ...self::ACTION, ...self::LINK],
            [...self::BOX, ...self::PAD, ...self::TEXT, ...self::FADE, ...self::TOOLTIP, ...self::POPUP, 'style.background_color', 'action.type', 'action.time', 'action.jump_at_position', 'action.activation_style.background_color', 'action.activation_style.text_color']];
        yield 'zoom' => [new ZoomInteraction(...$common, endTime: 3, box: $box), self::BOX,
            [...self::BOX]];
    }

    #[Test]
    public function it_builds_the_api_docs_example_import(): void
    {
        $interactions = [
            new TextInteraction(
                startTime: 0,
                endTime: 10,
                title: 'Text',
                box: new Box('70.04%', '25%', '50%', '20.17%', new Border(2, BorderStyle::Dashed, '#fff'), 15),
                backgroundColor: 'rgba(255, 255, 255, 0.9)',
                text: 'Your text here',
                padding: new Padding(top: 40),
                fadeIn: false,
                fadeOut: false,
                action: Action::jump(25.2),
            ),
            new LinkInteraction(
                startTime: 0,
                endTime: 10,
                title: 'link interaction',
                box: new Box('30%', '25%', '50%', '15%', borderRadius: 15),
                action: Action::link('https://example.com'),
            ),
            new ChapterInteraction(
                startTime: 5.0,
                endTime: 60.0,
                chapters: [new Chapter('Chapter one', 5.0, 15.0, 'One', 1, false), new Chapter('Chapter two', 15.0, 30.0, 'Two', 2, true)],
                title: 'Table of contents',
                position: MenuPosition::Right,
                startCollapsed: true,
                collapseAfterChapterClick: true,
                pauseWhenShown: true,
                useMenuLayout: true,
            ),
        ];

        $stub = json_decode((string) file_get_contents(__DIR__ . '/../../Fixtures/api-docs-example-interaction-import.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($stub);
        // The SDK leaves a null out instead of sending it.
        Arr::forget($stub, '0.action.link');
        // The SDK's link action carries no time.
        Arr::forget($stub, '1.action.time');

        $this->assertEquals($stub, array_map(fn (InteractionInput $interaction): array => $interaction->toArray(), $interactions));
    }

    #[Test]
    public function action_types_are_sent_as_their_names(): void
    {
        $this->assertSame(['type' => 'jump_to_interaction', 'question_id' => 9], Action::jumpToQuestion(9)->toArray());
        $this->assertSame(['type' => 'enable_subtitle', 'subtitle_route_key' => 1234], Action::enableSubtitle(1234)->toArray());
        $this->assertSame(['type' => 'delayed_jump', 'time' => 5.0], Action::delayedJump(5)->toArray());
    }

    #[Test]
    public function refs_link_a_jump_to_an_item_in_the_same_request(): void
    {
        $this->assertSame(['type' => 'jump_to_interaction', 'target_ref' => 'intro'], Action::jumpToInteractionRef('intro')->toArray());
        $this->assertSame(['type' => 'jump_to_chapter', 'target_ref' => 'part-1'], Action::jumpToChapterRef('part-1')->toArray());

        $chapters = new ChapterInteraction(startTime: 0, endTime: 9, chapters: [new Chapter('One', 1, 2, ref: 'part-1')], ref: 'toc')->toArray();

        $this->assertSame('toc', $chapters['ref']);
        $this->assertSame([['title' => 'One', 'start_time' => 1.0, 'end_time' => 2.0, 'ref' => 'part-1']], $chapters['chapters']);
    }

    #[Test]
    public function an_adaptive_condition_is_sent_with_numeric_action_codes(): void
    {
        $condition = new AdaptiveCondition(2, AdaptiveComparison::Within, 0, AdaptiveAction::jumpToQuestion(7));

        $this->assertSame(
            ['subject_sort_order' => 2, 'comparison' => '~', 'percentage' => 0, 'fallback_action_type' => 5, 'fallback_action_value' => 'question-id-7'],
            new ZoomInteraction(startTime: 1, endTime: 2, adaptiveCondition: $condition)->toArray()['adaptive_condition'],
        );
        $this->assertSame(['subject_sort_order' => 1, 'comparison' => '<', 'percentage' => 100], new AdaptiveCondition(1, AdaptiveComparison::LessThan, 100)->toArray());
    }

    #[Test]
    public function an_adaptive_compare_condition_leaves_unused_values_out(): void
    {
        $condition = new AdaptiveCompareCondition(
            AdaptiveCompareMode::LowestAbsolute,
            AdaptiveAction::none(),
            [new AdaptiveCompareTarget(1, AdaptiveAction::jumpToStart()), new AdaptiveCompareTarget(3, AdaptiveAction::jumpToInteraction(8))],
            [new AdaptiveTieOutcome(AdaptiveAction::showScoreScreen(), [1, 3]), new AdaptiveTieOutcome(AdaptiveAction::jumpToEnd())],
        );

        $this->assertSame([
            'comparison_mode' => 'lowest_absolute',
            'default_action_type' => 0,
            'targets' => [
                ['subject_sort_order' => 1, 'action_type' => 3],
                ['subject_sort_order' => 3, 'action_type' => 5, 'action_value' => 'interaction-id-8'],
            ],
            'tie_outcomes' => [
                ['action_type' => 6, 'subject_sort_orders' => [1, 3]],
                ['action_type' => 4],
            ],
        ], $condition->toArray());
    }

    #[Test]
    #[DataProvider('adaptiveJumpProvider')]
    public function an_adaptive_jump_is_sent_as_a_studio_time(float $seconds, string $expected): void
    {
        $jump = AdaptiveAction::jump($seconds);

        $this->assertSame(2, $jump->typeCode());
        $this->assertSame($expected, $jump->value);
    }

    /**
     * @return iterable<string, array{float, string}>
     */
    public static function adaptiveJumpProvider(): iterable
    {
        yield 'start' => [0, '00:00:00.00'];
        yield 'ninety seconds' => [90, '00:01:30.00'];
        yield 'rounds up into the next second' => [59.999, '00:01:00.00'];
        yield 'over an hour' => [3725.5, '01:02:05.50'];
    }

    /**
     * @param  Closure(): mixed  $build
     */
    #[Test]
    #[DataProvider('invalidAdaptiveProvider')]
    public function adaptive_input_outside_the_api_limits_throws(Closure $build): void
    {
        $this->expectException(InvalidArgumentException::class);

        $build();
    }

    /**
     * @return iterable<string, array{Closure(): mixed}>
     */
    public static function invalidAdaptiveProvider(): iterable
    {
        $target = new AdaptiveCompareTarget(1, AdaptiveAction::none());
        $tie = new AdaptiveTieOutcome(AdaptiveAction::none());

        yield 'negative jump' => [fn (): AdaptiveAction => AdaptiveAction::jump(-1)];
        yield 'subject position 0' => [fn (): AdaptiveCondition => new AdaptiveCondition(0, AdaptiveComparison::AtLeast, 50)];
        yield 'subject position 4' => [fn (): AdaptiveCondition => new AdaptiveCondition(4, AdaptiveComparison::AtLeast, 50)];
        yield 'percentage below 0' => [fn (): AdaptiveCondition => new AdaptiveCondition(1, AdaptiveComparison::AtLeast, -1)];
        yield 'percentage above 100' => [fn (): AdaptiveCondition => new AdaptiveCondition(1, AdaptiveComparison::AtLeast, 101)];
        yield 'target position 4' => [fn (): AdaptiveCompareTarget => new AdaptiveCompareTarget(4, AdaptiveAction::none())];
        yield 'no targets' => [fn (): AdaptiveCompareCondition => new AdaptiveCompareCondition(AdaptiveCompareMode::Highest, AdaptiveAction::none(), [])];
        yield '4 targets' => [fn (): AdaptiveCompareCondition => new AdaptiveCompareCondition(AdaptiveCompareMode::Highest, AdaptiveAction::none(), [$target, $target, $target, $target])];
        yield '4 tie outcomes' => [fn (): AdaptiveCompareCondition => new AdaptiveCompareCondition(AdaptiveCompareMode::Highest, AdaptiveAction::none(), [$target], [$tie, $tie, $tie, $tie])];
        yield 'tie with 4 subjects' => [fn (): AdaptiveTieOutcome => new AdaptiveTieOutcome(AdaptiveAction::none(), [1, 2, 3, 3])];
        yield 'tie subject position 0' => [fn (): AdaptiveTieOutcome => new AdaptiveTieOutcome(AdaptiveAction::none(), [0])];
    }

    #[Test]
    public function frame_fields_are_sent_without_an_action(): void
    {
        $frame = new FrameInteraction(startTime: 0, pauseVideo: true, continuePlaying: FrameContinuePlaying::ClickOutsideFrameElementOrFrameButton);

        $this->assertSame(['should_pause_video' => true, 'frame_continue_playing_option' => 'click_outside_frame_element_or_frame_button'], $frame->toArray()['action']);
    }

    #[Test]
    public function null_fields_are_left_out(): void
    {
        $this->assertSame(['type' => 'zoom', 'start_time' => 1.0, 'end_time' => 2.0], new ZoomInteraction(startTime: 1, endTime: 2)->toArray());
    }

    /**
     * @param  array<array-key, mixed>  $body
     * @return list<string>
     */
    private static function leafPaths(array $body, string $prefix = ''): array
    {
        $paths = [];

        foreach ($body as $key => $value) {
            $path = $prefix . $key;
            $paths = is_array($value) && ! array_is_list($value)
                ? [...$paths, ...self::leafPaths($value, $path . '.')]
                : [...$paths, $path];
        }

        return $paths;
    }
}
