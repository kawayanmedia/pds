<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Model;

use KawayanMedia\Pds\Support\Text;

/**
 * Row of the legacy pds_personal_info table.
 */
final class PersonalInfo
{
    public function __construct(
        public readonly string $lastName = '',
        public readonly string $firstName = '',
        public readonly string $middleName = '',
        public readonly string $extension = '',
        public readonly mixed $birthDate = null,
        public readonly string $birthPlace = '',
        public readonly string $sex = '',
        public readonly int $civilStatus = 0,
        public readonly string $citizenship = '',
        public readonly string $height = '',
        public readonly string $weight = '',
        public readonly string $bloodType = '',
        public readonly string $gsis = '',
        public readonly string $pagibig = '',
        public readonly string $philhealth = '',
        public readonly string $sss = '',
        public readonly string $residentialAddress = '',
        public readonly string $residentialZip = '',
        public readonly string $residentialTelephone = '',
        public readonly string $permanentAddress = '',
        public readonly string $permanentZip = '',
        public readonly string $permanentTelephone = '',
        public readonly string $email = '',
        public readonly string $cellphone = '',
        public readonly string $agencyEmployeeNumber = '',
        public readonly string $tin = '',
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            lastName: Text::blank($row['lname'] ?? ''),
            firstName: Text::blank($row['fname'] ?? ''),
            middleName: Text::blank($row['mname'] ?? ''),
            extension: Text::blank($row['extension'] ?? ''),
            birthDate: $row['birth_date'] ?? null,
            birthPlace: Text::blank($row['birth_place'] ?? ''),
            sex: strtoupper(Text::blank($row['sex'] ?? '')),
            civilStatus: (int) ($row['civil_status'] ?? 0),
            citizenship: Text::blank($row['citizenship'] ?? ''),
            height: Text::blank($row['height'] ?? ''),
            weight: Text::blank($row['weight'] ?? ''),
            bloodType: Text::blank($row['blood_type'] ?? ''),
            gsis: Text::blank($row['gsis'] ?? ''),
            pagibig: Text::blank($row['pagibig'] ?? ''),
            philhealth: Text::blank($row['philhealth'] ?? ''),
            sss: Text::blank($row['sss'] ?? ''),
            residentialAddress: Text::blank($row['res_address'] ?? ''),
            residentialZip: Text::blank($row['res_zip'] ?? ''),
            residentialTelephone: Text::blank($row['res_tel'] ?? ''),
            permanentAddress: Text::blank($row['permanent_address'] ?? ''),
            permanentZip: Text::blank($row['permanent_zip'] ?? ''),
            permanentTelephone: Text::blank($row['permanent_tel'] ?? ''),
            email: Text::blank($row['email'] ?? ''),
            cellphone: Text::blank($row['cp'] ?? ''),
            agencyEmployeeNumber: Text::blank($row['agency_employee_no'] ?? ''),
            tin: Text::blank($row['tin'] ?? ''),
        );
    }

    /**
     * Legacy column names, so the page layout can reference them directly.
     *
     * @return array<string, mixed>
     */
    public function toValues(): array
    {
        return [
            'lname' => $this->lastName,
            'fname' => $this->firstName,
            'mname' => $this->middleName,
            'extension' => $this->extension,
            'birth_date' => $this->birthDate,
            'birth_place' => $this->birthPlace,
            'sex' => $this->sex,
            'civil_status' => (string) $this->civilStatus,
            'citizenship' => $this->citizenship,
            'height' => $this->height,
            'weight' => $this->weight,
            'blood_type' => $this->bloodType,
            'gsis' => $this->gsis,
            'pagibig' => $this->pagibig,
            'philhealth' => $this->philhealth,
            'sss' => $this->sss,
            'res_address' => $this->residentialAddress,
            'res_zip' => $this->residentialZip,
            'res_tel' => $this->residentialTelephone,
            'permanent_address' => $this->permanentAddress,
            'permanent_zip' => $this->permanentZip,
            'permanent_tel' => $this->permanentTelephone,
            'email' => $this->email,
            'cp' => $this->cellphone,
            'agency_employee_no' => $this->agencyEmployeeNumber,
            'tin' => $this->tin,
        ];
    }

    public function fullName(): string
    {
        $name = trim($this->firstName . ' ' . $this->middleName . ' ' . $this->lastName);

        return $this->extension === '' ? $name : $name . ' ' . $this->extension;
    }

    public function isMale(): bool
    {
        return $this->sex === 'M';
    }
}
