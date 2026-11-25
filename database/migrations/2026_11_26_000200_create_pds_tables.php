<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CS Form 212 (Personal Data Sheet) sections.
 *
 * The Government models (PersonalInformation, FamilyBackground, …) and the
 * kawayanmedia/pds editor both key every section on `employee_id`; the
 * education rows add `level` so each of the five pre-printed rows is unique.
 */
return new class extends Migration
{
    /**
     * @return array<string, callable(Blueprint): void>
     */
    private function sections(): array
    {
        return [
            'pds_personal_info' => function (Blueprint $table): void {
                $table->string('lname', 64)->nullable();
                $table->string('fname', 64)->nullable();
                $table->string('mname', 64)->nullable();
                $table->string('extension', 16)->nullable();
                $table->date('birth_date')->nullable();
                $table->string('birth_place', 128)->nullable();
                $table->string('sex', 16)->nullable();
                $table->string('civil_status', 32)->nullable();
                $table->string('citizenship', 64)->nullable();
                $table->string('height', 32)->nullable();
                $table->string('weight', 32)->nullable();
                $table->string('blood_type', 8)->nullable();
                $table->string('gsis', 32)->nullable();
                $table->string('pagibig', 32)->nullable();
                $table->string('philhealth', 32)->nullable();
                $table->string('sss', 32)->nullable();
                $table->string('res_address', 255)->nullable();
                $table->string('res_zip', 16)->nullable();
                $table->string('res_tel', 32)->nullable();
                $table->string('permanent_address', 255)->nullable();
                $table->string('permanent_zip', 16)->nullable();
                $table->string('permanent_tel', 32)->nullable();
                $table->string('email', 128)->nullable();
                $table->string('cp', 32)->nullable();
                $table->string('agency_employee_no', 32)->nullable();
                $table->string('tin', 32)->nullable();
            },
            'pds_family_background' => function (Blueprint $table): void {
                $table->string('spouse_lname', 64)->nullable();
                $table->string('spouse_fname', 64)->nullable();
                $table->string('spouse_mname', 64)->nullable();
                $table->string('spouse_occupation', 128)->nullable();
                $table->string('spouse_employer', 128)->nullable();
                $table->string('spouse_biz_ad', 255)->nullable();
                $table->string('spouse_tel', 32)->nullable();
                $table->string('father_lname', 64)->nullable();
                $table->string('father_fname', 64)->nullable();
                $table->string('father_mname', 64)->nullable();
                $table->string('mother_lname', 64)->nullable();
                $table->string('mother_fname', 64)->nullable();
                $table->string('mother_mname', 64)->nullable();
            },
            'pds_children' => function (Blueprint $table): void {
                $table->string('children', 128)->nullable();
                $table->date('birth_date')->nullable();
            },
            'pds_education_background' => function (Blueprint $table): void {
                $table->unsignedTinyInteger('level')->default(1);
                $table->string('school_name', 128)->nullable();
                $table->string('degree_course', 128)->nullable();
                $table->string('year_graduated', 16)->nullable();
                $table->string('highest_grade', 32)->nullable();
                $table->string('attend_from', 16)->nullable();
                $table->string('attend_to', 16)->nullable();
                $table->string('scholarship', 128)->nullable();
            },
            'pds_eligibility' => function (Blueprint $table): void {
                $table->string('type', 128)->nullable();
                $table->string('rating', 32)->nullable();
                $table->date('date_exam_conferment')->nullable();
                $table->string('place_exam_conferment', 128)->nullable();
                $table->string('license_no', 64)->nullable();
                $table->date('license_release_date')->nullable();
            },
            'pds_work_experience' => function (Blueprint $table): void {
                $table->string('inclusive_date_from', 32)->nullable();
                $table->string('inclusive_date_to', 32)->nullable();
                $table->string('position', 128)->nullable();
                $table->string('company', 128)->nullable();
                $table->string('monthly_salary', 32)->nullable();
                $table->string('salary_grade', 32)->nullable();
                $table->string('status', 32)->nullable();
                $table->string('movement', 32)->nullable();
                $table->string('govt_service', 32)->nullable();
            },
            'pds_organization' => function (Blueprint $table): void {
                $table->string('name', 128)->nullable();
                $table->string('inclusive_date_from', 32)->nullable();
                $table->string('inclusive_date_to', 32)->nullable();
                $table->string('number_of_hours', 32)->nullable();
                $table->string('position', 128)->nullable();
            },
            'pds_training' => function (Blueprint $table): void {
                $table->string('name', 128)->nullable();
                $table->string('date_from', 32)->nullable();
                $table->string('date_to', 32)->nullable();
                $table->string('number_hours', 32)->nullable();
                $table->string('conducted_by', 128)->nullable();
                $table->string('location', 128)->nullable();
            },
            'pds_other_info' => function (Blueprint $table): void {
                $table->text('special_skills')->nullable();
                $table->text('recognition')->nullable();
                $table->text('membership_organization')->nullable();
            },
            'pds_questions' => function (Blueprint $table): void {
                $table->unsignedTinyInteger('question_no');
                $table->boolean('answer')->nullable();
                $table->text('details')->nullable();
            },
            'pds_references' => function (Blueprint $table): void {
                $table->string('name', 128)->nullable();
                $table->string('address', 255)->nullable();
                $table->string('tel_no', 32)->nullable();
                $table->string('ctc_no', 32)->nullable();
                $table->string('issue_at', 32)->nullable();
                $table->string('issue_on', 32)->nullable();
            },
        ];
    }

    public function up(): void
    {
        foreach ($this->sections() as $name => $schema) {
            if (Schema::hasTable($name)) {
                continue;
            }

            Schema::create($name, function (Blueprint $table) use ($name, $schema): void {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('employee_id');
                $schema($table);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['company_id', 'employee_id'], $name.'_company_employee_index');

                if ($name === 'pds_education_background') {
                    $table->unique(['company_id', 'employee_id', 'level'], 'pds_education_level_unique');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(array_keys($this->sections())) as $name) {
            Schema::dropIfExists($name);
        }
    }
};
