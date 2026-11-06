<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Config;

use KawayanMedia\Pds\Model\EducationLevel;
use KawayanMedia\Pds\Support\Dates;

/**
 * Every coordinate, font size and row height the legacy Pds_preview controller
 * hard-coded, in one place.
 *
 * The four form pages are drawn, in order, onto the templates named in
 * $templates. Like the legacy controller, the templates are placed at (1, 1)
 * scaled to 210 mm wide, so every coordinate below is expressed in that same
 * scaled space. Pass templateWidth: null to draw a template at its natural
 * size instead. Page 1 holds the absolute field placements plus the children and
 * education blocks; pages 2-4 are repeating tables (career, learning,
 * references) with a few absolute extras.
 */
final class PdsLayout
{
    public const PAGE_PERSONAL = 'page1';

    public const PAGE_CAREER = 'page2';

    public const PAGE_LEARNING = 'page3';

    public const PAGE_QUESTIONS = 'page4';

    /**
     * The 30 pre-printed name boxes shared by the three name rows.
     */
    public const NAME_BOX_X = [
        39.0, 44.0, 49.0, 54.0, 60.0, 65.0, 70.0, 75.0, 81.0, 86.0,
        91.0, 96.0, 101.0, 106.0, 111.0, 116.0, 121.0, 126.0, 131.0, 137.0,
        142.0, 147.0, 152.0, 157.0, 163.0, 168.0, 173.0, 178.0, 183.0, 188.0,
    ];

    /**
     * @param array<string, string> $templates      page key => absolute template path
     * @param array<int, int>       $textColor      rgb
     * @param array<string, mixed>  $education      block anchors and column positions
     * @param array<string, mixed>  $questions      yes/no columns and row positions
     * @param array<string, float>  $ctc            certificate of candidacy block
     */
    public function __construct(
        public readonly array $templates = [],
        public readonly string $pageOrientation = 'P',
        public readonly string $pageUnit = 'mm',
        public readonly string $pageSize = 'Legal',
        public readonly float $templateX = 1.0,
        public readonly float $templateY = 1.0,
        public readonly ?float $templateWidth = 210.0,
        public readonly bool $compress = true,
        public readonly string $fontFamily = 'Arial',
        public readonly float $baseFontSize = 12.0,
        public readonly array $textColor = [0, 0, 0],
        public readonly PageLayout $personalPage = new PageLayout(),
        public readonly TableLayout $children = new TableLayout([], 145.0, 5.0),
        public readonly array $education = [],
        public readonly TableLayout $eligibility = new TableLayout([], 32.0, 7.0),
        public readonly TableLayout $work = new TableLayout([], 115.0, 7.0),
        public readonly TableLayout $volunteer = new TableLayout([], 31.0, 7.0),
        public readonly TableLayout $training = new TableLayout([], 105.0, 7.0),
        public readonly TableLayout $otherInfo = new TableLayout([], 258.0, 7.0),
        public readonly TableLayout $references = new TableLayout([], 233.0, 4.0),
        public readonly array $questions = [],
        public readonly array $ctc = [],
    ) {
    }

    /**
     * The iHRMIS preset: the exact coordinates of the legacy controller.
     *
     * @param array<string, string>|null $templates
     */
    public static function legacyIhrmis(?array $templates = null, bool $compress = true): self
    {
        return new self(
            templates: $templates ?? self::bundledTemplates(),
            compress: $compress,
            personalPage: PageLayout::make(...self::personalPlacements()),
            children: new TableLayout(
                columns: [
                    Placement::text('name', 110.0, 0.0, 10.0),
                    Placement::date('birth_date', 170.0, 0.0, Dates::SLASH_DATE, 10.0),
                ],
                startY: 150.0,
                rowHeight: 4.70,
                endY: 203.0,
                templatePage: self::PAGE_PERSONAL,
            ),
            education: self::educationBlock(),
            eligibility: new TableLayout(
                columns: [
                    Placement::text('type', 8.0, 0.0, 7.0, 25),
                    Placement::text('rating', 58.0, 0.0, 7.0),
                    Placement::date('date_exam_conferment', 75.0, 0.0, Dates::SLASH_DATE, 7.0),
                    Placement::text('place_exam_conferment', 98.0, 0.0, 7.0),
                    Placement::text('license_no', 167.0, 0.0, 7.0),
                    Placement::date('license_release_date', 188.0, 0.0, Dates::SLASH_DATE, 8.0),
                ],
                startY: 32.6,
                rowHeight: 7.35,
                endY: 78.0,
                templatePage: self::PAGE_CAREER,
            ),
            work: new TableLayout(
                columns: [
                    Placement::date('inclusive_date_from', 7.0, 0.0, Dates::SLASH_DATE, 8.0),
                    Placement::text('inclusive_date_to', 22.0, 0.0, 8.0),
                    Placement::text('position', 39.0, 0.0, 8.0, 20),
                    Placement::text('company', 75.0, 0.0, 8.0, 35),
                    Placement::text('monthly_salary', 132.0, 0.0, 8.0),
                    Placement::text('salary_grade', 150.0, 0.0, 8.0),
                    Placement::text('status', 166.0, 0.0, 8.0),
                    Placement::text('govt_service', 190.0, 0.0, 8.0),
                ],
                startY: 115.2,
                rowHeight: 7.35,
                endY: 293.0,
                templatePage: self::PAGE_CAREER,
            ),
            volunteer: new TableLayout(
                columns: [
                    Placement::text('name', 8.0, 0.0, 12.0),
                    Placement::date('inclusive_date_from', 95.0, 0.0, Dates::SLASH_DATE, 8.0),
                    Placement::date('inclusive_date_to', 117.0, 0.0, Dates::SLASH_DATE, 8.0),
                    Placement::text('number_of_hours', 138.0, 0.0, 12.0),
                    Placement::text('position', 155.0, 0.0, 12.0),
                ],
                startY: 32.6,
                rowHeight: 7.35,
                endY: 78.0,
                templatePage: self::PAGE_LEARNING,
            ),
            training: new TableLayout(
                columns: [
                    Placement::text('name', 8.0, 0.0, 8.0, 50),
                    Placement::date('date_from', 95.0, 0.0, Dates::SLASH_DATE, 8.0),
                    Placement::date('date_to', 117.0, 0.0, Dates::SLASH_DATE, 8.0),
                    Placement::text('number_hours', 138.0, 0.0, 8.0),
                    Placement::text('conducted_by', 155.0, 0.0, 8.0, 25),
                ],
                startY: 105.6,
                rowHeight: 7.35,
                endY: 239.0,
                templatePage: self::PAGE_LEARNING,
            ),
            otherInfo: new TableLayout(
                columns: [
                    Placement::text('special_skills', 8.0, 0.0, 8.0),
                    Placement::text('recognition', 58.0, 0.0, 8.0),
                    Placement::text('membership_organization', 155.0, 0.0, 8.0, 25),
                ],
                startY: 259.4,
                rowHeight: 7.35,
                endY: 311.0,
                templatePage: self::PAGE_LEARNING,
            ),
            references: new TableLayout(
                columns: [
                    Placement::text('name', 8.0, 0.0, 10.0),
                    Placement::text('address', 72.0, 0.0, 10.0),
                    Placement::text('tel_no', 134.0, 0.0, 10.0),
                ],
                startY: 237.0,
                rowHeight: 4.28,
                endY: 238.5,
                templatePage: self::PAGE_QUESTIONS,
            ),
            questions: self::questionBlock(),
            ctc: ['x' => 15.0, 'y' => 275.0, 'lineSpan' => 13.0, 'dateX' => 90.0],
        );
    }

    /**
     * Points the layout at a directory holding page1.pdf … page4.pdf.
     */
    public static function fromDirectory(string $directory): self
    {
        return self::legacyIhrmis(self::templatesIn($directory));
    }

    /**
     * @return array<string, string>
     */
    public static function bundledTemplates(): array
    {
        return self::templatesIn(dirname(__DIR__, 2) . '/resources/templates');
    }

    /**
     * @return array<string, string>
     */
    public static function templatesIn(string $directory): array
    {
        $directory = rtrim($directory, '/\\');

        return [
            self::PAGE_PERSONAL => $directory . '/page1.pdf',
            self::PAGE_CAREER => $directory . '/page2.pdf',
            self::PAGE_LEARNING => $directory . '/page3.pdf',
            self::PAGE_QUESTIONS => $directory . '/page4.pdf',
        ];
    }

    /**
     * @param array<string, string> $templates
     */
    public function withTemplates(array $templates): self
    {
        return new self(
            templates: $templates,
            pageOrientation: $this->pageOrientation,
            pageUnit: $this->pageUnit,
            pageSize: $this->pageSize,
            templateX: $this->templateX,
            templateY: $this->templateY,
            templateWidth: $this->templateWidth,
            compress: $this->compress,
            fontFamily: $this->fontFamily,
            baseFontSize: $this->baseFontSize,
            textColor: $this->textColor,
            personalPage: $this->personalPage,
            children: $this->children,
            education: $this->education,
            eligibility: $this->eligibility,
            work: $this->work,
            volunteer: $this->volunteer,
            training: $this->training,
            otherInfo: $this->otherInfo,
            references: $this->references,
            questions: $this->questions,
            ctc: $this->ctc,
        );
    }

    public function template(string $page): string
    {
        return $this->templates[$page] ?? '';
    }

    public function hasTemplate(string $page): bool
    {
        return is_file($this->template($page));
    }

    /**
     * @return array<string, mixed>
     */
    public static function educationBlock(): array
    {
        return [
            'anchors' => [
                EducationLevel::Elementary->value => 233.0,
                EducationLevel::Secondary->value => 246.0,
                EducationLevel::Vocational->value => 259.0,
                EducationLevel::College->value => 274.0,
                EducationLevel::GraduateStudies->value => 290.0,
            ],
            'schoolX' => 39.0,
            'degreeX' => 78.0,
            'yearX' => 107.0,
            'gradeX' => 130.0,
            'fromX' => 150.0,
            'toX' => 163.0,
            'scholarshipX' => 177.0,
            'schoolWidth' => 23,
            'degreeWidth' => 15,
            'lines' => 4,
            'lineHeight' => 3.0,
            'fontSize' => 10.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function questionBlock(): array
    {
        return [
            'yesX' => 138.5,
            'noX' => 164.0,
            'y' => [
                1 => 14.0,
                2 => 35.0,
                3 => 61.0,
                4 => 82.0,
                5 => 108.0,
                6 => 134.0,
                7 => 155.0,
                8 => 190.0,
                9 => 202.0,
                10 => 215.0,
            ],
        ];
    }

    /**
     * @return list<Placement>
     */
    public static function personalPlacements(): array
    {
        return [
            // 2. Name — one character per pre-printed box.
            Placement::boxed('lname', self::NAME_BOX_X, 53.0),
            Placement::boxed('fname', self::NAME_BOX_X, 59.0),
            Placement::boxed('mname', self::NAME_BOX_X, 65.0),
            Placement::text('extension', 189.0, 65.0),

            // 3-4. Birth date, address, sex and civil status.
            Placement::date('birth_date', 67.0, 71.0, Dates::MONTH_DAY_YEAR),
            Placement::twoLine('res_address', 126.0, 71.0, 4.0),
            Placement::text('birth_place', 39.0, 75.0),
            Placement::check('sex', 'M', 41.5, 80.0),
            Placement::check('sex', 'F', 58.0, 80.0),
            Placement::check('civil_status', '1', 41.5, 85.0),
            Placement::check('civil_status', '2', 41.5, 89.0),
            Placement::check('civil_status', '3', 41.5, 94.5),
            Placement::check('civil_status', '4', 64.0, 85.0),
            Placement::check('civil_status', '5', 64.0, 89.0),
            Placement::check('civil_status', '6', 64.0, 94.5),

            // 5-7. Citizenship and the physical/identification block.
            Placement::text('citizenship', 39.0, 103.0),
            Placement::text('height', 39.0, 108.2),
            Placement::text('weight', 39.0, 113.4),
            Placement::text('blood_type', 39.0, 118.6),
            Placement::text('gsis', 39.0, 122.6),
            Placement::text('pagibig', 39.0, 127.6),
            Placement::text('philhealth', 39.0, 131.6),
            Placement::text('sss', 39.0, 136.6),

            // 8. Residential (right column) and permanent address.
            Placement::text('res_zip', 126.0, 85.0),
            Placement::text('res_tel', 126.0, 90.0),
            Placement::twoLine('permanent_address', 126.0, 95.0, 4.0),
            Placement::text('permanent_zip', 126.0, 114.0),
            Placement::text('permanent_tel', 126.0, 118.0),
            Placement::text('email', 126.0, 122.3),
            Placement::text('cp', 126.0, 127.2),
            Placement::text('agency_employee_no', 126.0, 131.5),
            Placement::text('tin', 126.0, 135.8),

            // III. Family background — spouse.
            Placement::text('spouse_lname', 39.0, 146.0),
            Placement::text('spouse_fname', 39.0, 150.3),
            Placement::text('spouse_mname', 39.0, 154.6),
            Placement::text('spouse_occupation', 39.0, 159.6),
            Placement::text('spouse_employer', 39.0, 164.6),
            Placement::text('spouse_biz_ad', 39.0, 169.6),
            Placement::text('spouse_tel', 39.0, 174.6),

            // III. Family background — parents.
            Placement::text('father_lname', 57.0, 183.6),
            Placement::text('father_fname', 57.0, 188.1),
            Placement::text('father_mname', 57.0, 192.6),
            Placement::text('mother_lname', 57.0, 197.1),
            Placement::text('mother_fname', 57.0, 201.6),
            Placement::text('mother_mname', 57.0, 206.1),
        ];
    }
}
