<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\StructuredData;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Hirtz\Skeleton\Helpers\StructuredData;
use Override;
use Yii;

class JobPosting extends Thing
{
    final public const string EMPLOYMENT_FULL_TIME = 'FULL_TIME';
    final public const string EMPLOYMENT_PART_TIME = 'PART_TIME';
    final public const string EMPLOYMENT_CONTRACTOR = 'CONTRACTOR';
    final public const string EMPLOYMENT_TEMPORARY = 'TEMPORARY';
    final public const string EMPLOYMENT_INTERN = 'INTERN';
    final public const string EMPLOYMENT_VOLUNTEER = 'VOLUNTEER';
    final public const string EMPLOYMENT_PER_DIEM = 'PER_DIEM';
    final public const string EMPLOYMENT_OTHER = 'OTHER';

    protected ?string $type = 'JobPosting';
    protected ?string $title = null;
    protected ?string $description = null;
    protected ?DateTimeInterface $datePosted = null;
    protected ?DateTimeInterface $validThrough = null;

    /**
     * @var list<string>
     */
    protected array $employmentTypes = [];

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $hiringOrganization = null;

    /**
     * @var array{streetAddress?: string|null, postalCode?: string|null, addressLocality?: string|null, addressRegion?: string|null, addressCountry?: string|null}|null
     */
    protected ?array $address = null;
    protected bool $remote = false;
    protected ?string $applicantCountry = null;
    protected ?string $identifier = null;

    public function title(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Google requires the full HTML description shown on the page.
     */
    public function description(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function datePosted(?DateTimeInterface $datePosted): static
    {
        $this->datePosted = $datePosted;
        return $this;
    }

    public function validThrough(?DateTimeInterface $validThrough): static
    {
        $this->validThrough = $validThrough;
        return $this;
    }

    /**
     * @param list<string> $employmentTypes
     */
    public function employmentTypes(array $employmentTypes): static
    {
        $this->employmentTypes = $employmentTypes;
        return $this;
    }

    /**
     * @param Organization|array<string, mixed>|null $organization
     */
    public function hiringOrganization(Organization|array|null $organization): static
    {
        $this->hiringOrganization = $organization instanceof Organization ? $organization->build() : $organization;
        return $this;
    }

    /**
     * @param array{streetAddress?: string|null, postalCode?: string|null, addressLocality?: string|null, addressRegion?: string|null, addressCountry?: string|null}|null $address
     */
    public function address(?array $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function remote(bool $remote = true, ?string $applicantCountry = null): static
    {
        $this->remote = $remote;
        $this->applicantCountry = $applicantCountry;
        return $this;
    }

    public function identifier(?string $identifier): static
    {
        $this->identifier = $identifier;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->hiringOrganization ??= Organization::make()->build();
        parent::configure();
    }

    #[Override]
    public function isVisible(): bool
    {
        return $this->title
            && $this->description
            && $this->datePosted !== null
            && ($this->hiringOrganization['name'] ?? null)
            && ($this->remote ? $this->getApplicantCountry() : ($this->address['streetAddress'] ?? null))
            && !$this->isExpired()
            && parent::isVisible();
    }

    #[Override]
    protected function getProperties(): array
    {
        $country = $this->getApplicantCountry();

        return [
            'title' => $this->title,
            'description' => $this->description,
            'datePosted' => StructuredData::date($this->datePosted),
            'validThrough' => StructuredData::date($this->validThrough),
            'employmentType' => $this->employmentTypes ?: null,
            'hiringOrganization' => $this->hiringOrganization,
            'jobLocation' => $this->address ? [
                '@type' => 'Place',
                'address' => ['@type' => 'PostalAddress', ...$this->address],
            ] : null,
            'jobLocationType' => $this->remote ? 'TELECOMMUTE' : null,
            'applicantLocationRequirements' => $this->remote && $country
                ? ['@type' => 'Country', 'name' => $country]
                : null,
            'identifier' => $this->identifier !== null ? [
                '@type' => 'PropertyValue',
                'name' => $this->hiringOrganization['name'] ?? null,
                'value' => $this->identifier,
            ] : null,
            ...parent::getProperties(),
        ];
    }

    protected function getApplicantCountry(): ?string
    {
        return $this->applicantCountry ?: ($this->address['addressCountry'] ?? null);
    }

    /**
     * Google penalizes a posting still marked up after it expired, so it counts by day in the application's zone.
     */
    protected function isExpired(): bool
    {
        if ($this->validThrough === null) {
            return false;
        }

        $today = (new DateTimeImmutable('now', new DateTimeZone(Yii::$app->getTimeZone())))->format('Y-m-d');
        return substr((string)StructuredData::date($this->validThrough), 0, 10) < $today;
    }
}
