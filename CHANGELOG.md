# Changelog

## [0.2.0](https://github.com/hihaho/hihaho-php/compare/v0.1.0...v0.2.0) (2026-11-19)


### ⚠ BREAKING CHANGES

* createViewerToken() takes $variables as array<string, string> and StatisticsFilter takes them as array<string, string|null>, both as name => value instead of a list. MultipleChoiceQuestion, MultipleResponseQuestion and ImageQuestion take $answers as the required second parameter, before $text, and it must not be empty. AiTasks::start() refuses the GenerateEvaluationRubrics and AnalyzeQuestionPerformance task types.
* **interactions:** objectFit on ImageInteraction, FrameInteraction and PauseInteraction takes ObjectFit instead of a string. Replace 'cover' with ObjectFit::Cover and '' with ObjectFit::Default.

### Features

* **interactions:** take the image fit as an ObjectFit enum ([#3](https://github.com/hihaho/hihaho-php/issues/3)) ([c489ac3](https://github.com/hihaho/hihaho-php/commit/c489ac39aa333c4f89155053e483f5fd3dc841d5))
* send viewer variables by name, require choice answers, stop starting two AI task types ([2befd2f](https://github.com/hihaho/hihaho-php/commit/2befd2f43eef79cc35b9bcc5c23e93388ee3e6e2))

## 0.1.0 (2026-11-12)


### Features

* first public release of the hihaho PHP SDK ([d245e8c](https://github.com/hihaho/hihaho-php/commit/d245e8ce4f2e739848b321c3d34c74c50c52a49f))

## Changelog

All notable changes to `hihaho/hihaho-php` are documented in this file.
