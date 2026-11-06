<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Model;

use KawayanMedia\Pds\Support\Dates;

/**
 * Everything the four printed pages need for one employee.
 *
 * Build it from the legacy table rows with fromArray(), or construct it
 * directly from the typed models.
 */
final class Pds
{
    /**
     * @param list<Child>             $children
     * @param list<EducationEntry>    $education
     * @param list<Eligibility>       $eligibility
     * @param list<WorkExperience>    $work
     * @param list<VolunteerWork>     $volunteer
     * @param list<Training>          $training
     * @param list<OtherInfo>         $otherInfo
     * @param array<int, bool>        $answers   question number => answer (true = Yes)
     * @param list<Reference>         $references
     */
    public function __construct(
        public readonly string $employeeId,
        public readonly PersonalInfo $personal,
        public readonly ?FamilyBackground $family = null,
        public readonly array $children = [],
        public readonly array $education = [],
        public readonly array $eligibility = [],
        public readonly array $work = [],
        public readonly array $volunteer = [],
        public readonly array $training = [],
        public readonly array $otherInfo = [],
        public readonly array $answers = [],
        public readonly array $references = [],
    ) {
    }

    /**
     * Maps the legacy iHRMIS rows. Accepts models or plain arrays for every
     * section; rows are ordered the way the legacy queries ordered them.
     *
     * @param array<string, mixed> $rows
     */
    public static function fromArray(array $rows): self
    {
        $personal = $rows['personal'] ?? [];
        $family = $rows['family'] ?? null;

        $children = array_map(
            static fn (mixed $row): Child => $row instanceof Child ? $row : Child::fromArray((array) $row),
            (array) ($rows['children'] ?? []),
        );

        usort($children, static fn (Child $left, Child $right): int => self::compare(
            self::sortKey($left->birthDate),
            self::sortKey($right->birthDate),
        ));

        $work = array_map(
            static fn (mixed $row): WorkExperience => $row instanceof WorkExperience
                ? $row
                : WorkExperience::fromArray((array) $row),
            (array) ($rows['work'] ?? []),
        );

        usort($work, static fn (WorkExperience $left, WorkExperience $right): int => self::compare(
            self::sortKey($right->dateFrom),
            self::sortKey($left->dateFrom),
        ));

        $training = array_map(
            static fn (mixed $row): Training => $row instanceof Training ? $row : Training::fromArray((array) $row),
            (array) ($rows['training'] ?? []),
        );

        usort($training, static fn (Training $left, Training $right): int => self::compare(
            self::sortKey($right->dateFrom),
            self::sortKey($left->dateFrom),
        ));

        return new self(
            employeeId: (string) ($rows['employee_id'] ?? ''),
            personal: $personal instanceof PersonalInfo ? $personal : PersonalInfo::fromArray((array) $personal),
            family: $family === null
                ? null
                : ($family instanceof FamilyBackground ? $family : FamilyBackground::fromArray((array) $family)),
            children: array_values($children),
            education: array_map(
                static fn (mixed $row): EducationEntry => $row instanceof EducationEntry
                    ? $row
                    : EducationEntry::fromArray((array) $row),
                (array) ($rows['education'] ?? []),
            ),
            eligibility: array_map(
                static fn (mixed $row): Eligibility => $row instanceof Eligibility
                    ? $row
                    : Eligibility::fromArray((array) $row),
                (array) ($rows['eligibility'] ?? []),
            ),
            work: array_values($work),
            volunteer: array_map(
                static fn (mixed $row): VolunteerWork => $row instanceof VolunteerWork
                    ? $row
                    : VolunteerWork::fromArray((array) $row),
                (array) ($rows['volunteer'] ?? []),
            ),
            training: array_values($training),
            otherInfo: array_map(
                static fn (mixed $row): OtherInfo => $row instanceof OtherInfo
                    ? $row
                    : OtherInfo::fromArray((array) $row),
                (array) ($rows['other_info'] ?? []),
            ),
            answers: self::answers($rows),
            references: array_map(
                static fn (mixed $row): Reference => $row instanceof Reference
                    ? $row
                    : Reference::fromArray((array) $row),
                (array) ($rows['references'] ?? []),
            ),
        );
    }

    /**
     * @return list<EducationEntry>
     */
    public function educationFor(EducationLevel $level): array
    {
        return array_values(array_filter(
            $this->education,
            static fn (EducationEntry $entry): bool => $entry->level === $level,
        ));
    }

    /**
     * true = Yes, false = No, null = the question was not answered.
     */
    public function answer(int $questionNumber): ?bool
    {
        return $this->answers[$questionNumber] ?? null;
    }

    public function firstReference(): ?Reference
    {
        return $this->references[0] ?? null;
    }

    /**
     * @param array<string, mixed> $rows
     *
     * @return array<int, bool>
     */
    private static function answers(array $rows): array
    {
        $answers = [];

        foreach ((array) ($rows['questions'] ?? []) as $row) {
            $row = (array) $row;
            $number = (int) ($row['question_no'] ?? 0);

            if ($number === 0) {
                continue;
            }

            $answers[$number] = (int) ($row['answer'] ?? 0) === 1;
        }

        foreach ((array) ($rows['answers'] ?? []) as $number => $answer) {
            $answers[(int) $number] = (bool) $answer;
        }

        ksort($answers);

        return $answers;
    }

    /**
     * ISO date, or an empty string when the value is not a date so that
     * undated rows always sort last.
     */
    private static function sortKey(mixed $date): string
    {
        return Dates::parse($date)?->format('Y-m-d') ?? '';
    }

    private static function compare(string $left, string $right): int
    {
        if ($left === $right) {
            return 0;
        }

        if ($left === '') {
            return 1;
        }

        if ($right === '') {
            return -1;
        }

        return $left <=> $right;
    }
}
