<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Questions;

use Hihaho\Sdk\Enums\QuestionType;

/**
 * A job application form. The answers and any uploaded file are sent to `receiverEmailAddress`,
 * which must be a real address with a mail server.
 */
final readonly class VacancyQuestion extends QuestionInput
{
    public function __construct(
        float $activeAt,
        public string $receiverEmailAddress,
        ?string $text = null,
        public ?string $companyNameLabel = null,
        public ?string $firstNameLabel = null,
        public ?string $lastNameLabel = null,
        public ?string $uploadInstructionalPlaceholder = null,
        public ?bool $allowFileUpload = null,
        QuestionSettings $settings = new QuestionSettings(),
        ?string $ref = null,
    ) {
        parent::__construct($activeAt, $text, $settings, $ref);
    }

    public function type(): QuestionType
    {
        return QuestionType::Vacancy;
    }

    protected function typeFields(): array
    {
        return ['markup' => ['vacancy' => [
            'receiver_email_address' => $this->receiverEmailAddress,
            'company_name_label' => $this->companyNameLabel,
            'first_name_label' => $this->firstNameLabel,
            'last_name_label' => $this->lastNameLabel,
            'upload_instructional_placeholder' => $this->uploadInstructionalPlaceholder,
            'should_allow_file_upload' => $this->allowFileUpload,
        ]]];
    }
}
